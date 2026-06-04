<?php

namespace Drupal\ai_domain_chatbot\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Crawls configured domains and searches indexed content.
 *
 * Search uses a three-tier approach:
 *  Tier 1 — exact phrase match (highest boost)
 *  Tier 2 — all keywords present in the page
 *  Tier 3 — any keyword present (broadest net)
 */
class DomainSearcher {

  private array $cookieJars = [];

  /**
   * Conversational stopwords — includes NL question words so
   * "Can you share Dog Man books?" → keywords: ["dog", "man", "books"].
   */
  private const STOPWORDS = [
    'a','an','the','is','it','in','on','at','to','for','of','and','or',
    'but','by','with','from','as','are','was','be','been','has','have',
    // conversational
    'can','you','your','could','would','will','should','may','might',
    'do','does','did','i','me','my','we','our','us',
    'tell','show','find','give','share','get','help','need','want',
    'looking','searching','seeking','trying',
    'about','please','pls','any','some','all','more','also',
    // question words
    'what','which','where','who','how','why','when',
    // noise
    'url','link','page','site','website','book','books',
  ];

  public function __construct(
    protected Connection $database,
    protected ClientInterface $httpClient,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected TimeInterface $time,
  ) {}

  // -------------------------------------------------------------------------
  // Public API
  // -------------------------------------------------------------------------

  /**
   * Main search — natural language aware, three-tier relevance ranking.
   */
  public function search(string $query, array $domains, int $limit = 8): array {
    $normalized   = $this->normalize($query);
    $key_phrase   = $this->extractKeyPhrase($normalized);
    $keywords     = $this->extractKeywords($normalized);

    if (empty($keywords)) {
      return [];
    }

    $clean_domains = $this->cleanDomains($domains);

    // Pull every page that matches any keyword (URL + title + content).
    $rows = $this->fetchCandidates($keywords, $clean_domains);

    if (empty($rows)) {
      return [];
    }

    // Score all candidates.
    $scored = $this->scoreRows($rows, $keywords, $key_phrase);

    // Sort by score descending.
    usort($scored, static fn($a, $b) => $b['score'] - $a['score']);

    // Deduplicate by domain+title and return top $limit.
    return $this->deduplicate($scored, $limit);
  }

  // -------------------------------------------------------------------------
  // Query processing
  // -------------------------------------------------------------------------

  /**
   * Lowercase, remove accents, strip punctuation.
   */
  private function normalize(string $text): string {
    $text = $this->removeAccents(mb_strtolower($text));
    return preg_replace('/[^\w\s]/u', ' ', $text);
  }

  /**
   * Extract a key phrase by removing conversational wrappers.
   * "Can you share Dog Man books?" → "dog man"
   * "I am looking for literacy resources" → "literacy resources"
   */
  private function extractKeyPhrase(string $normalized): string {
    // Strip leading conversational patterns.
    $patterns = [
      '/^(can you |could you |please |pls |do you have |show me |tell me about |'
      . 'i am looking for |i\'m looking for |looking for |find me |'
      . 'recommend |give me |share |what is |what are |i need |'
      . 'do you |are there |is there )+/u',
    ];
    $phrase = $normalized;
    foreach ($patterns as $p) {
      $phrase = preg_replace($p, '', $phrase);
    }
    // Strip trailing noise ("please", "books", etc.)
    $phrase = preg_replace('/([\s]+(please|pls|for me|for kids|for students|url|link))+$/u', '', trim($phrase));

    return trim($phrase);
  }

  /**
   * Extract keywords: remove stopwords, expand plurals/singulars.
   */
  private function extractKeywords(string $normalized): array {
    $words    = array_filter(explode(' ', $normalized));
    $keywords = array_values(array_diff($words, self::STOPWORDS));

    $expanded = [];
    foreach ($keywords as $kw) {
      if (mb_strlen($kw) < 2) {
        continue;
      }
      $expanded[] = $kw;
      // Plural/singular expansion.
      if (str_ends_with($kw, 'ies') && mb_strlen($kw) > 4) {
        $expanded[] = mb_substr($kw, 0, -3) . 'y'; // stories→story
        $expanded[] = mb_substr($kw, 0, -1);         // cookies→cookie
      }
      elseif (str_ends_with($kw, 'ing') && mb_strlen($kw) > 5) {
        $expanded[] = mb_substr($kw, 0, -3);          // reading→read
        $expanded[] = mb_substr($kw, 0, -3) . 'e';   // writing→write
      }
      elseif (str_ends_with($kw, 'ed') && mb_strlen($kw) > 4) {
        $expanded[] = mb_substr($kw, 0, -2);          // survived→surviv (loose)
        $expanded[] = mb_substr($kw, 0, -1);          // survived→survive
      }
      elseif (str_ends_with($kw, 's') && mb_strlen($kw) > 3) {
        $expanded[] = mb_substr($kw, 0, -1);          // dogs→dog
      }
      else {
        $expanded[] = $kw . 's';                      // dog→dogs
      }
      // Common irregulars.
      if ($kw === 'men')    { $expanded[] = 'man'; }
      if ($kw === 'man')    { $expanded[] = 'men'; }
      if ($kw === 'women')  { $expanded[] = 'woman'; }
      if ($kw === 'children') { $expanded[] = 'child'; }
      if ($kw === 'child')  { $expanded[] = 'children'; }
    }

    return array_unique(array_filter($expanded));
  }

  // -------------------------------------------------------------------------
  // Database fetch
  // -------------------------------------------------------------------------

  /**
   * Fetch all pages from the configured domains that match any keyword
   * in URL, title, or content. No row limit — PHP scoring picks the best.
   */
  private function fetchCandidates(array $keywords, array $clean_domains): array {
    $q  = $this->database->select('ai_domain_chatbot_pages', 'p')
      ->fields('p', ['url', 'title', 'content', 'domain'])
      ->condition('p.domain', $clean_domains, 'IN');

    $or = $q->orConditionGroup();
    foreach ($keywords as $kw) {
      $like = '%' . $this->database->escapeLike($kw) . '%';
      $or->condition('p.url', $like, 'LIKE');
      $or->condition('p.title', $like, 'LIKE');
      $or->condition('p.content', $like, 'LIKE');
    }
    $q->condition($or);

    return $q->execute()->fetchAll();
  }

  // -------------------------------------------------------------------------
  // Scoring
  // -------------------------------------------------------------------------

  /**
   * Score each candidate row using three tiers.
   */
  private function scoreRows(array $rows, array $keywords, string $key_phrase): array {
    $scored = [];

    foreach ($rows as $row) {
      $title   = mb_strtolower($this->removeAccents($row->title));
      $content = mb_strtolower($this->removeAccents($row->content));
      $url     = mb_strtolower($row->url);
      $score   = 0;
      $matched = 0;

      // --- Tier 1: Phrase match (exact + partial bigrams) ---
      if ($key_phrase && mb_strlen($key_phrase) > 2) {
        // Exact full phrase.
        if (mb_stripos($title, $key_phrase) !== FALSE) {
          $score += 200;
        }
        if (mb_stripos($content, $key_phrase) !== FALSE) {
          $score += 100;
        }
        if (str_contains($url, str_replace(' ', '-', $key_phrase))) {
          $score += 150;
        }
        // Partial phrase — check consecutive word pairs (bigrams).
        // "launch scholastic canada year" → check "launch scholastic",
        // "scholastic canada", "canada year" separately.
        $phrase_words = explode(' ', $key_phrase);
        for ($pi = 0; $pi < count($phrase_words) - 1; $pi++) {
          $bigram = $phrase_words[$pi] . ' ' . $phrase_words[$pi + 1];
          if (mb_strlen($bigram) > 4) {
            if (mb_stripos($title, $bigram) !== FALSE) {
              $score += 80;
            }
            if (mb_stripos($content, $bigram) !== FALSE) {
              $score += 60;
            }
          }
        }
      }

      // --- Tier 2 & 3: Keyword scoring ---
      foreach ($keywords as $kw) {
        $pattern      = '/\b' . preg_quote($kw, '/') . '\b/u';
        $title_hits   = preg_match_all($pattern, $title);
        $content_hits = preg_match_all($pattern, $content);

        if ($title_hits > 0 || $content_hits > 0) {
          $matched++;
          $score += $title_hits * 5;   // title match weighted higher
          $score += $content_hits;
        }
        // URL slug match.
        if (str_contains($url, $kw)) {
          $score += 50;
          $matched++;
        }
      }

      // --- Keyword coverage bonus ---
      // Pages matching MORE unique keywords rank above pages matching
      // one keyword many times (prevents "ready-to-read" flooding "read" searches).
      $total_keywords = count($keywords);
      if ($total_keywords > 0) {
        $coverage = $matched / $total_keywords;
        $score   += (int) ($coverage * 60);
      }

      $scored[] = ['row' => $row, 'score' => $score];
    }

    return $scored;
  }

  // -------------------------------------------------------------------------
  // Helpers
  // -------------------------------------------------------------------------

  private function cleanDomains(array $domains): array {
    return array_unique(array_filter(array_map(
      static fn(string $d) => parse_url(trim($d), PHP_URL_HOST) ?: trim($d),
      $domains,
    )));
  }

  private function deduplicate(array $scored, int $limit): array {
    // First pass: pick top 2 results per domain to ensure diversity.
    $per_domain  = [];
    $seen_titles = [];
    $results     = [];

    foreach ($scored as $item) {
      $row    = $item['row'];
      $key    = $row->domain . '|' . $row->title;
      $domain = $row->domain;

      if (isset($seen_titles[$key])) {
        continue;
      }
      $seen_titles[$key] = TRUE;
      $per_domain[$domain]   = $per_domain[$domain] ?? 0;

      // Allow up to 3 results per domain in the first pass.
      if ($per_domain[$domain] < 3) {
        $per_domain[$domain]++;
        $results[] = [
          'url'    => $row->url,
          'title'  => $row->title,
          'content' => $this->excerpt($row->content, []),
          'domain' => $domain,
          'score'  => $item['score'],
        ];
      }
    }

    // Second pass: fill remaining slots with highest-scoring remaining results.
    if (count($results) < $limit) {
      foreach ($scored as $item) {
        if (count($results) >= $limit) {
          break;
        }
        $row = $item['row'];
        $key = $row->domain . '|' . $row->title;
        $already = FALSE;
        foreach ($results as $r) {
          if ($r['url'] === $row->url) {
            $already = TRUE;
            break;
          }
        }
        if (!$already && isset($seen_titles[$key])) {
          $results[] = [
            'url'    => $row->url,
            'title'  => $row->title,
            'content' => $this->excerpt($row->content, []),
            'domain' => $row->domain,
            'score'  => $item['score'],
          ];
        }
      }
    }

    // Sort final set by score descending.
    usort($results, static fn($a, $b) => $b['score'] - $a['score']);

    return array_slice($results, 0, $limit);
  }

  private function excerpt(string $content, array $keywords): string {
    $content = strip_tags($content);
    $len     = mb_strlen($content);

    // Short pages: send the full content so nothing is missed.
    if ($len <= 3000) {
      return $content;
    }

    // Longer pages: keyword context + always append the tail (OCR/alt text
    // is appended at the end of content during crawl).
    $middle = '';
    foreach ($keywords as $kw) {
      $pos = mb_stripos($content, $kw);
      if ($pos !== FALSE) {
        $middle = mb_substr($content, max(0, $pos - 200), 1500);
        break;
      }
    }
    if (!$middle) {
      $middle = mb_substr($content, 0, 1500);
    }

    // Always include the last 800 chars where alt text / OCR text is stored.
    $tail = mb_substr($content, -800);

    return $middle . ' [...] ' . $tail;
  }

  /**
   * Normalize accented characters to ASCII for language-agnostic matching.
   */
  private function removeAccents(string $text): string {
    if (function_exists('transliterator_transliterate')) {
      return transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
    }
    $from = ['á','à','ä','â','ã','é','è','ë','ê','í','ì','ï','î','ó','ò','ö','ô','õ','ú','ù','ü','û','ý','ÿ','ñ','ç',
             'Á','À','Ä','Â','Ã','É','È','Ë','Ê','Í','Ì','Ï','Î','Ó','Ò','Ö','Ô','Õ','Ú','Ù','Ü','Û','Ý','Ñ','Ç'];
    $to   = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','y','y','n','c',
             'A','A','A','A','A','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','Y','N','C'];
    return str_replace($from, $to, $text);
  }

  // -------------------------------------------------------------------------
  // Crawling
  // -------------------------------------------------------------------------

  public function crawlUrl(string $url, string $domain): bool {
    try {
      $response = $this->httpClient->get($url, [
        'timeout'         => 20,
        'allow_redirects' => TRUE,
        'verify'          => FALSE,
        'cookies'         => $this->cookieJar($domain),
        'headers'         => $this->browserHeaders(),
      ]);

      $html = (string) $response->getBody();
      $doc  = new \DOMDocument('1.0', 'UTF-8');
      @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);

      $title_nodes = $doc->getElementsByTagName('title');
      $title       = $title_nodes->length
        ? trim($title_nodes->item(0)->textContent)
        : $url;

      foreach (['script', 'style', 'noscript'] as $tag) {
        foreach (iterator_to_array($doc->getElementsByTagName($tag)) as $node) {
          $node->parentNode->removeChild($node);
        }
      }

      $body_nodes = $doc->getElementsByTagName('body');
      $content    = $body_nodes->length
        ? preg_replace('/\s+/', ' ', trim($body_nodes->item(0)->textContent))
        : '';

      if (empty($content)) {
        return FALSE;
      }

      // Extract image alt texts AND run OCR on images missing alt text.
      // This handles pages like client logo grids where logos are images.
      $alt_texts  = [];
      $scheme_host = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
      foreach ($doc->getElementsByTagName('img') as $img) {
        $alt = trim($img->getAttribute('alt'));
        if ($alt && mb_strlen($alt) > 1) {
          // Alt text exists — use it directly.
          $alt_texts[] = $alt;
        }
        else {
          // No alt text — try OCR on the image.
          $src = trim($img->getAttribute('src'));
          if ($src) {
            $ocr_text = $this->ocrImage($src, $scheme_host, $domain);
            if ($ocr_text) {
              $alt_texts[] = $ocr_text;
            }
          }
        }
      }
      if (!empty($alt_texts)) {
        $content .= ' ' . implode(' ', $alt_texts);
      }

      $content = $this->sanitizeUtf8($content);
      $title   = $this->sanitizeUtf8($title);

      $existing = $this->database->select('ai_domain_chatbot_pages', 'p')
        ->fields('p', ['id'])
        ->condition('p.url', $url)
        ->execute()
        ->fetchField();

      $data = [
        'url'        => $url,
        'domain'     => $domain,
        'title'      => mb_substr($title, 0, 512),
        'content'    => $content,
        'crawled_at' => $this->time->getRequestTime(),
      ];

      if ($existing) {
        $this->database->update('ai_domain_chatbot_pages')
          ->fields($data)->condition('id', $existing)->execute();
      }
      else {
        $this->database->insert('ai_domain_chatbot_pages')
          ->fields($data)->execute();
      }

      return TRUE;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('ai_domain_chatbot')->error(
        'Crawl failed for @url: @msg',
        ['@url' => $url, '@msg' => $e->getMessage()],
      );
      return FALSE;
    }
  }

  public function discoverLinks(string $url, string $domain, string $scheme): array {
    try {
      $response = $this->httpClient->get($url, [
        'timeout'         => 15,
        'allow_redirects' => TRUE,
        'verify'          => FALSE,
        'cookies'         => $this->cookieJar($domain),
        'headers'         => $this->browserHeaders(),
      ]);

      $html = (string) $response->getBody();
      $doc  = new \DOMDocument('1.0', 'UTF-8');
      @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);

      $links = [];
      foreach ($doc->getElementsByTagName('a') as $a) {
        $href = trim($a->getAttribute('href'));
        if (!$href || str_starts_with($href, '#')) {
          continue;
        }
        if (!str_starts_with($href, 'http')) {
          $href = $scheme . '://' . $domain . '/' . ltrim($href, '/');
        }
        if (!in_array(parse_url($href, PHP_URL_SCHEME), ['http', 'https'], TRUE)) {
          continue;
        }
        if (parse_url($href, PHP_URL_HOST) === $domain) {
          $links[] = $href;
        }
      }

      return array_unique($links);
    }
    catch (\Exception) {
      return [];
    }
  }

  public function crawlDomain(string $base_url, int $max_pages = 20): int {
    $domain  = parse_url($base_url, PHP_URL_HOST);
    $scheme  = parse_url($base_url, PHP_URL_SCHEME) ?? 'https';
    $visited = [];
    $queue   = [$base_url];
    $crawled = 0;

    while (!empty($queue) && $crawled < $max_pages) {
      $url = array_shift($queue);
      if (isset($visited[$url])) {
        continue;
      }
      $visited[$url] = TRUE;

      if ($this->crawlUrl($url, $domain)) {
        $crawled++;
        foreach ($this->discoverLinks($url, $domain, $scheme) as $link) {
          if (!isset($visited[$link]) && !in_array($link, $queue, TRUE)) {
            $queue[] = $link;
          }
        }
      }
    }

    return $crawled;
  }

  /**
   * Download an image and extract text using Tesseract OCR.
   * Only used for images that have no alt text.
   */
  private function ocrImage(string $src, string $scheme_host, string $domain): string {
    // Skip tiny icons, SVGs, data URIs, and tracking pixels.
    if (str_starts_with($src, 'data:')
      || str_ends_with(strtolower(parse_url($src, PHP_URL_PATH) ?? ''), '.svg')
      || str_ends_with(strtolower(parse_url($src, PHP_URL_PATH) ?? ''), '.gif')
    ) {
      return '';
    }

    // Resolve relative URLs.
    if (!str_starts_with($src, 'http')) {
      $src = $scheme_host . '/' . ltrim($src, '/');
    }

    // Only OCR images from the same domain.
    if (parse_url($src, PHP_URL_HOST) !== $domain) {
      return '';
    }

    try {
      $response = $this->httpClient->get($src, [
        'timeout' => 10,
        'verify'  => FALSE,
        'headers' => $this->browserHeaders(),
      ]);

      $image_data = (string) $response->getBody();
      if (strlen($image_data) < 500) {
        // Skip tiny images — likely tracking pixels or icons.
        return '';
      }

      // Write to a temp file and run Tesseract.
      $tmp = tempnam(sys_get_temp_dir(), 'ocr_') . '.png';
      file_put_contents($tmp, $image_data);

      $output_base = $tmp . '_out';
      exec('tesseract ' . escapeshellarg($tmp) . ' ' . escapeshellarg($output_base) . ' 2>/dev/null');

      $result = '';
      if (file_exists($output_base . '.txt')) {
        $result = trim(file_get_contents($output_base . '.txt'));
        unlink($output_base . '.txt');
      }
      unlink($tmp);

      // Only return meaningful text (more than 2 characters).
      return mb_strlen($result) > 2 ? $result : '';
    }
    catch (\Exception) {
      return '';
    }
  }

  private function sanitizeUtf8(string $text): string {
    $clean = iconv('UTF-8', 'UTF-8//IGNORE', $text);
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $clean ?: $text);
    return trim($clean ?? $text);
  }

  private function browserHeaders(): array {
    return [
      'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
      'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'Accept-Language' => 'en-US,en;q=0.9',
    ];
  }

  private function cookieJar(string $domain): CookieJar {
    if (!isset($this->cookieJars[$domain])) {
      $this->cookieJars[$domain] = CookieJar::fromArray([], $domain);
    }
    return $this->cookieJars[$domain];
  }

}
