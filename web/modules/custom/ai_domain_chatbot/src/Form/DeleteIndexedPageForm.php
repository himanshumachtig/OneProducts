<?php

namespace Drupal\ai_domain_chatbot\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation form before deleting an indexed page.
 */
class DeleteIndexedPageForm extends ConfirmFormBase {

  protected ?object $page = NULL;

  public function __construct(protected Connection $database) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('database'));
  }

  public function getFormId(): string {
    return 'ai_domain_chatbot_delete_indexed_page';
  }

  public function getQuestion(): \Drupal\Core\StringTranslation\TranslatableMarkup {
    return $this->t('Are you sure you want to delete this indexed page?');
  }

  public function getDescription(): \Drupal\Core\StringTranslation\TranslatableMarkup {
    $title = $this->page ? $this->page->title : '';
    $url   = $this->page ? $this->page->url   : '';
    return $this->t(
      'This will remove <strong>@title</strong> (@url) from the search index. This action cannot be undone.',
      ['@title' => $title, '@url' => $url],
    );
  }

  public function getConfirmText(): \Drupal\Core\StringTranslation\TranslatableMarkup {
    return $this->t('Delete');
  }

  public function getCancelUrl(): Url {
    return Url::fromRoute('ai_domain_chatbot.indexed');
  }

  public function buildForm(array $form, FormStateInterface $form_state, int $id = 0): array {
    $this->page = $this->database->select('ai_domain_chatbot_pages', 'p')
      ->fields('p', ['id', 'title', 'url', 'domain'])
      ->condition('p.id', $id)
      ->execute()
      ->fetchObject();

    if (!$this->page) {
      $this->messenger()->addError($this->t('Page not found in index.'));
      return $this->redirect('ai_domain_chatbot.indexed');
    }

    $form['id'] = ['#type' => 'value', '#value' => $id];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->database->delete('ai_domain_chatbot_pages')
      ->condition('id', $form_state->getValue('id'))
      ->execute();

    $this->messenger()->addStatus($this->t('The page has been removed from the index.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
