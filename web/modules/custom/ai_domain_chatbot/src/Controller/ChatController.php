<?php

namespace Drupal\ai_domain_chatbot\Controller;

use Drupal\ai_domain_chatbot\Batch\CrawlBatch;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ChatController extends ControllerBase {

  public function chat(Request $request): JsonResponse {
    return new JsonResponse(['error' => 'Use the main chatbot widget.'], 400);
  }

  public function crawl(): array|RedirectResponse {
    $config  = $this->config('ai_domain_chatbot.settings');
    $domains = array_filter(array_map('trim', explode("\n", $config->get('domains') ?? '')));

    if (empty($domains)) {
      return [
        '#markup' => '<div class="messages messages--warning">No domains configured. <a href="/admin/config/ai-chatbot">Add domains first.</a></div>',
      ];
    }

    $max_pages = (int) ($config->get('max_pages') ?? 100);

    // Build one batch operation per domain.
    $operations = [];
    foreach ($domains as $entry) {
      $entry = trim($entry);
      if (empty($entry)) {
        continue;
      }
      $base_url = strpos($entry, 'http') === 0 ? $entry : 'https://' . $entry;
      $operations[] = [
        [CrawlBatch::class, 'processDomain'],
        [$base_url, $max_pages],
      ];
    }

    $batch = [
      'title'            => $this->t('Crawling configured websites…'),
      'operations'       => $operations,
      'finished'         => [CrawlBatch::class, 'finished'],
      'progress_message' => $this->t('Processing domain @current of @total…'),
      'init_message'     => $this->t('Starting crawl…'),
      'error_message'    => $this->t('Crawl encountered an error.'),
    ];

    batch_set($batch);

    // batch_process() returns a RedirectResponse to the batch progress page.
    return batch_process('/admin/config/ai-chatbot');
  }

}
