<?php

namespace Drupal\ai_domain_chatbot\Form;

use Drupal\ai_domain_chatbot\Batch\CrawlBatch;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CrawlForm extends FormBase {

  public function __construct(protected Connection $database) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('database'));
  }

  public function getFormId(): string {
    return 'ai_domain_chatbot_crawl_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config  = $this->config('ai_domain_chatbot.settings');
    $domains = array_filter(array_map('trim', explode("\n", $config->get('domains') ?? '')));

    if (empty($domains)) {
      $form['empty'] = [
        '#markup' => '<div class="messages messages--warning">'
          . $this->t('No domains configured. <a href="/admin/config/ai-chatbot">Add domains first.</a>')
          . '</div>',
      ];
      return $form;
    }

    // Get current page counts per domain.
    $counts = [];
    $rows   = $this->database->query(
      'SELECT domain, COUNT(*) AS cnt FROM {ai_domain_chatbot_pages} GROUP BY domain'
    )->fetchAll();
    foreach ($rows as $row) {
      $counts[$row->domain] = (int) $row->cnt;
    }

    // Build checkbox options with current index info.
    $options = [];
    $default = [];
    foreach ($domains as $entry) {
      $entry      = trim($entry);
      $host       = parse_url($entry, PHP_URL_HOST) ?: $entry;
      $indexed    = $counts[$host] ?? 0;
      $label      = '<strong>' . htmlspecialchars($entry) . '</strong>'
        . ' <small style="color:#666">(' . $indexed . ' pages currently indexed)</small>';
      $options[$entry] = $label;
      $default[$entry] = $entry; // all checked by default
    }

    $max_pages = (int) ($config->get('max_pages') ?? 100);

    $form['intro'] = [
      '#markup' => '<p>' . $this->t('Select which websites to crawl. Already-indexed pages will be updated.') . '</p>',
    ];

    $form['domains'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Domains to crawl'),
      '#options'       => $options,
      '#default_value' => $default,
      '#required'      => TRUE,
    ];

    $form['max_pages'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Max pages per domain'),
      '#default_value' => $max_pages,
      '#min'           => 5,
      '#max'           => 500,
      '#description'   => $this->t('How many pages to crawl from each selected domain.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Start Crawl'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type'  => 'link',
      '#title' => $this->t('Cancel'),
      '#url'   => \Drupal\Core\Url::fromRoute('ai_domain_chatbot.settings'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $selected  = array_filter($form_state->getValue('domains'));
    $max_pages = (int) $form_state->getValue('max_pages');

    if (empty($selected)) {
      $this->messenger()->addWarning($this->t('No domains selected.'));
      return;
    }

    $operations = [];
    foreach ($selected as $entry) {
      $base_url    = strpos($entry, 'http') === 0 ? $entry : 'https://' . $entry;
      $operations[] = [
        [CrawlBatch::class, 'processDomain'],
        [$base_url, $max_pages],
      ];
    }

    $batch = [
      'title'            => $this->t('Crawling @count domain(s)…', ['@count' => count($selected)]),
      'operations'       => $operations,
      'finished'         => [CrawlBatch::class, 'finished'],
      'progress_message' => $this->t('Processing domain @current of @total…'),
      'init_message'     => $this->t('Starting crawl…'),
      'error_message'    => $this->t('Crawl encountered an error.'),
    ];

    batch_set($batch);
    $form_state->setRedirectUrl(\Drupal\Core\Url::fromRoute('ai_domain_chatbot.settings'));
  }

}
