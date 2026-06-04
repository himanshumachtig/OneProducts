<?php

namespace Drupal\ai_domain_chatbot\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation form before deleting all indexed pages for a domain.
 */
class DeleteDomainIndexForm extends ConfirmFormBase {

  protected string $domain = '';
  protected int $pageCount = 0;

  public function __construct(protected Connection $database) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('database'));
  }

  public function getFormId(): string {
    return 'ai_domain_chatbot_delete_domain_index';
  }

  public function getQuestion(): \Drupal\Core\StringTranslation\TranslatableMarkup {
    return $this->t('Delete all indexed pages for @domain?', ['@domain' => $this->domain]);
  }

  public function getDescription(): \Drupal\Core\StringTranslation\TranslatableMarkup {
    return $this->t(
      'This will permanently remove all <strong>@count indexed pages</strong> for <strong>@domain</strong> from the search index. The live website is not affected. You can re-crawl at any time to restore the index.',
      ['@count' => $this->pageCount, '@domain' => $this->domain],
    );
  }

  public function getConfirmText(): \Drupal\Core\StringTranslation\TranslatableMarkup {
    return $this->t('Yes, delete all @count pages', ['@count' => $this->pageCount]);
  }

  public function getCancelUrl(): Url {
    return Url::fromRoute('ai_domain_chatbot.indexed');
  }

  public function buildForm(array $form, FormStateInterface $form_state, string $domain = ''): array {
    $this->domain    = urldecode($domain);
    $this->pageCount = (int) $this->database->select('ai_domain_chatbot_pages', 'p')
      ->condition('p.domain', $this->domain)
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($this->pageCount === 0) {
      $this->messenger()->addWarning($this->t('No indexed pages found for @domain.', ['@domain' => $this->domain]));
      return $this->redirect('ai_domain_chatbot.indexed');
    }

    $form['domain'] = ['#type' => 'value', '#value' => $this->domain];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $domain  = $form_state->getValue('domain');
    $deleted = $this->database->delete('ai_domain_chatbot_pages')
      ->condition('domain', $domain)
      ->execute();

    $this->messenger()->addStatus(
      $this->t('Removed @count indexed pages for @domain.', ['@count' => $deleted, '@domain' => $domain])
    );
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
