<?php

namespace Drupal\ai_domain_chatbot\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class IndexedPagesController extends ControllerBase {

  public function __construct(protected Connection $database) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('database'));
  }

  public function list(Request $request): array {
    $domain_filter = $request->query->get('domain', '');
    $search_filter = $request->query->get('search', '');

    // Total count for summary.
    $total_query = $this->database->select('ai_domain_chatbot_pages', 'p')->countQuery();
    $total = (int) $total_query->execute()->fetchField();

    // Domain summary.
    $domain_counts = $this->database->query(
      'SELECT domain, COUNT(*) AS cnt FROM {ai_domain_chatbot_pages} GROUP BY domain ORDER BY cnt DESC'
    )->fetchAllKeyed();

    // Build the main listing query.
    $query = $this->database->select('ai_domain_chatbot_pages', 'p')
      ->fields('p', ['id', 'url', 'title', 'domain', 'crawled_at']);

    if ($domain_filter) {
      $query->condition('p.domain', $domain_filter);
    }
    if ($search_filter) {
      $or = $query->orConditionGroup()
        ->condition('p.title', '%' . $this->database->escapeLike($search_filter) . '%', 'LIKE')
        ->condition('p.url', '%' . $this->database->escapeLike($search_filter) . '%', 'LIKE');
      $query->condition($or);
    }

    $query->orderBy('p.domain')->orderBy('p.crawled_at', 'DESC');

    // Pager.
    $count_query = clone $query;
    $page_count  = (int) $count_query->countQuery()->execute()->fetchField();
    $per_page    = 50;
    $page        = (int) $request->query->get('page', 0);
    $query->range($page * $per_page, $per_page);

    $rows = $query->execute()->fetchAll();

    // Domain filter links with per-domain delete button.
    $domain_links = [];
    $base_params  = array_filter(['search' => $search_filter]);
    $all_url      = Url::fromRoute('ai_domain_chatbot.indexed', $base_params)->toString();
    $domain_links[] = '<a href="' . $all_url . '" ' . (!$domain_filter ? 'style="font-weight:bold"' : '') . '>All (' . $total . ')</a>';
    foreach ($domain_counts as $domain => $cnt) {
      $params     = $base_params + ['domain' => $domain];
      $filter_url = Url::fromRoute('ai_domain_chatbot.indexed', $params)->toString();
      $delete_url = Url::fromRoute('ai_domain_chatbot.indexed_delete_domain', ['domain' => urlencode($domain)])->toString();
      $active     = $domain_filter === $domain ? 'style="font-weight:bold"' : '';
      $domain_links[] = '<a href="' . $filter_url . '" ' . $active . '>' . htmlspecialchars($domain) . ' (' . $cnt . ')</a>'
        . ' <a href="' . $delete_url . '" title="Delete all indexed pages for ' . htmlspecialchars($domain) . '" '
        . 'style="color:#cc0000;font-size:0.85em;margin-left:4px">[Delete all]</a>';
    }

    // Build table rows.
    $table_rows = [];
    foreach ($rows as $row) {
      $delete_url = Url::fromRoute('ai_domain_chatbot.indexed_delete', ['id' => $row->id],
        ['query' => ['destination' => \Drupal::request()->getRequestUri()]]
      )->toString();
      $table_rows[] = [
        htmlspecialchars($row->domain),
        '<a href="' . htmlspecialchars($row->url) . '" target="_blank">' . htmlspecialchars($row->title ?: $row->url) . '</a>',
        '<small>' . htmlspecialchars($row->url) . '</small>',
        date('Y-m-d H:i', $row->crawled_at),
        '<a href="' . $delete_url . '">Delete</a>',
      ];
    }

    // Pager links.
    $total_pages = max(1, (int) ceil($page_count / $per_page));
    $pager_links = [];
    $pager_params = array_filter(['domain' => $domain_filter, 'search' => $search_filter]);
    for ($i = 0; $i < $total_pages; $i++) {
      $p = $pager_params + ['page' => $i];
      $p_url = Url::fromRoute('ai_domain_chatbot.indexed', $p)->toString();
      $pager_links[] = ($i === $page)
        ? '<strong>[' . ($i + 1) . ']</strong>'
        : '<a href="' . $p_url . '">' . ($i + 1) . '</a>';
    }

    $search_url = Url::fromRoute('ai_domain_chatbot.indexed')->toString();

    $output = '<p>';
    $output .= '<strong>Filter by domain:</strong> ' . implode(' | ', $domain_links);
    $output .= '</p>';

    $output .= '<form method="get" action="' . $search_url . '" style="margin-bottom:1em">';
    if ($domain_filter) {
      $output .= '<input type="hidden" name="domain" value="' . htmlspecialchars($domain_filter) . '">';
    }
    $output .= '<input type="text" name="search" value="' . htmlspecialchars($search_filter) . '" placeholder="Search title or URL…" style="padding:4px;width:300px">';
    $output .= ' <button type="submit">Search</button>';
    if ($search_filter) {
      $clear_url = Url::fromRoute('ai_domain_chatbot.indexed', array_filter(['domain' => $domain_filter]))->toString();
      $output .= ' <a href="' . $clear_url . '">Clear</a>';
    }
    $output .= '</form>';

    $output .= '<p>Showing ' . count($rows) . ' of ' . $page_count . ' pages' . ($domain_filter ? ' for ' . htmlspecialchars($domain_filter) : '') . '.</p>';

    $output .= '<table style="width:100%;border-collapse:collapse">';
    $output .= '<thead><tr style="background:#f5f5f5">';
    foreach (['Domain', 'Title', 'URL', 'Crawled', 'Action'] as $h) {
      $output .= '<th style="padding:6px 10px;text-align:left;border-bottom:2px solid #ddd">' . $h . '</th>';
    }
    $output .= '</tr></thead><tbody>';
    foreach ($table_rows as $i => $tr) {
      $bg = $i % 2 === 0 ? '#fff' : '#f9f9f9';
      $output .= '<tr style="background:' . $bg . '">';
      foreach ($tr as $td) {
        $output .= '<td style="padding:6px 10px;border-bottom:1px solid #eee">' . $td . '</td>';
      }
      $output .= '</tr>';
    }
    $output .= '</tbody></table>';

    if (count($pager_links) > 1) {
      $output .= '<p style="margin-top:1em">Pages: ' . implode(' ', $pager_links) . '</p>';
    }

    $output .= '<p style="margin-top:1.5em">';
    $output .= '<a href="/admin/config/ai-chatbot" class="button">← Settings</a> ';
    $output .= '<a href="/admin/config/ai-chatbot/crawl" class="button button--primary">Crawl Now</a>';
    $output .= '</p>';

    return ['#markup' => $output, '#allowed_tags' => array_merge(\Drupal\Component\Utility\Xss::getAdminTagList(), ['table', 'thead', 'tbody', 'tr', 'th', 'td', 'form', 'input', 'button', 'small', 'strong'])];
  }


}
