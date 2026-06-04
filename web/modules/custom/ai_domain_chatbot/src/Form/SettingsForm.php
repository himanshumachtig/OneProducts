<?php

namespace Drupal\ai_domain_chatbot\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames(): array {
    return ['ai_domain_chatbot.settings'];
  }

  public function getFormId(): string {
    return 'ai_domain_chatbot_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('ai_domain_chatbot.settings');

    $form['domains'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Configured Domains'),
      '#description'   => $this->t('Enter one domain or URL per line. The chatbot will ONLY answer from content crawled from these sites. Examples:<br><code>export.scholastic.com</code><br><code>https://scholastic.asia/en</code>'),
      '#default_value' => $config->get('domains') ?? '',
      '#rows'          => 8,
    ];

    $form['max_pages'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Max pages to crawl per domain'),
      '#description'   => $this->t('How many pages to crawl from each domain. Higher = more content indexed but slower crawl. Recommended: 50–200.'),
      '#default_value' => $config->get('max_pages') ?? 100,
      '#min'           => 10,
      '#max'           => 500,
    ];

    $form['crawl'] = [
      '#type'  => 'details',
      '#title' => $this->t('Crawl Websites'),
      '#open'  => TRUE,
    ];

    $form['crawl']['crawl_link'] = [
      '#markup' => '<p>'
        . $this->t('After saving your settings, <a href="/admin/config/ai-chatbot/crawl">click here to crawl the configured websites</a> and index their content.')
        . '</p><p>'
        . $this->t('<a href="/admin/config/ai-chatbot/indexed">View all indexed pages</a> — browse, search, and delete indexed content.')
        . '</p>',
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('ai_domain_chatbot.settings')
      ->set('domains', $form_state->getValue('domains'))
      ->set('max_pages', (int) $form_state->getValue('max_pages'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
