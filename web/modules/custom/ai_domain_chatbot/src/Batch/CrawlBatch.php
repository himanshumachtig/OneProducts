<?php

namespace Drupal\ai_domain_chatbot\Batch;

/**
 * Batch callbacks for domain crawling with progress tracking.
 */
class CrawlBatch {

  /**
   * Batch operation: crawl one domain incrementally.
   *
   * Called repeatedly by Drupal's batch system until $context['finished'] = 1.
   */
  public static function processDomain(string $base_url, int $max_pages, array &$context): void {
    $searcher = \Drupal::service('ai_domain_chatbot.searcher');

    if (empty($context['sandbox'])) {
      $context['sandbox']['queue']   = [$base_url];
      $context['sandbox']['visited'] = [];
      $context['sandbox']['crawled'] = 0;
      $context['sandbox']['failed']  = 0;
      $context['sandbox']['domain']  = parse_url($base_url, PHP_URL_HOST);
      $context['sandbox']['scheme']  = parse_url($base_url, PHP_URL_SCHEME) ?? 'https';
      $context['sandbox']['max']     = $max_pages;
    }

    $sb = &$context['sandbox'];

    // Process up to 3 URLs per batch call to stay within time limits.
    $processed = 0;
    while (!empty($sb['queue']) && $sb['crawled'] < $sb['max'] && $processed < 3) {
      $url = array_shift($sb['queue']);
      if (isset($sb['visited'][$url])) {
        continue;
      }
      $sb['visited'][$url] = TRUE;

      if ($searcher->crawlUrl($url, $sb['domain'])) {
        $sb['crawled']++;
        // Discover more links from this page.
        $links = $searcher->discoverLinks($url, $sb['domain'], $sb['scheme']);
        foreach ($links as $link) {
          if (!isset($sb['visited'][$link]) && !in_array($link, $sb['queue'])) {
            $sb['queue'][] = $link;
          }
        }
      }
      else {
        $sb['failed']++;
      }
      $processed++;
      // Small pause between requests to avoid triggering rate limiting.
      usleep(500000); // 0.5 seconds
    }

    // Store results for the finished callback.
    $context['results'][$sb['domain']] = [
      'crawled' => $sb['crawled'],
      'failed'  => $sb['failed'],
      'base_url' => $base_url,
    ];

    $context['message'] = t('Crawling <strong>@domain</strong>: @count pages indexed so far…', [
      '@domain' => $sb['domain'],
      '@count'  => $sb['crawled'],
    ]);

    if (empty($sb['queue']) || $sb['crawled'] >= $sb['max']) {
      $context['finished'] = 1;
    }
    else {
      // Estimate progress: pages crawled vs max expected.
      $context['finished'] = min(0.99, $sb['crawled'] / $sb['max']);
    }
  }

  /**
   * Batch finished callback.
   */
  public static function finished(bool $success, array $results, array $operations): void {
    if ($success) {
      $items = '';
      foreach ($results as $domain => $data) {
        $items .= '<li>Crawled <strong>' . htmlspecialchars($domain) . '</strong>: '
          . (int) $data['crawled'] . ' pages indexed.</li>';
      }
      $message = \Drupal\Core\Render\Markup::create(
        '<strong>Crawl complete!</strong><ul>' . $items . '</ul>'
      );
      \Drupal::messenger()->addStatus($message);
    }
    else {
      \Drupal::messenger()->addError(t('Crawl encountered errors. Check the recent log messages.'));
    }
  }

}
