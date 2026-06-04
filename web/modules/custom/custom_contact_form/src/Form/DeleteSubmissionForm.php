<?php

namespace Drupal\custom_contact_form\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class DeleteSubmissionForm extends ConfirmFormBase {

  protected $id;
  protected $record;

  public function getFormId() {
    return 'custom_contact_form_delete';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;
    $this->record = \Drupal::database()->select('custom_contact_form_submissions', 's')
      ->fields('s')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$this->record) {
      $this->messenger()->addError($this->t('Submission not found.'));
      return $this->redirect('custom_contact_form.submissions');
    }

    return parent::buildForm($form, $form_state);
  }

  public function getQuestion() {
    return $this->t('Are you sure you want to delete the submission from @name?', [
      '@name' => $this->record->name,
    ]);
  }

  public function getDescription() {
    return $this->t('This action cannot be undone. Submission: @name &lt;@email&gt;, Phone: @phone', [
      '@name'  => $this->record->name,
      '@email' => $this->record->email,
      '@phone' => $this->record->phone,
    ]);
  }

  public function getCancelUrl() {
    return Url::fromRoute('custom_contact_form.submissions');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::database()->delete('custom_contact_form_submissions')
      ->condition('id', $this->id)
      ->execute();

    $this->messenger()->addStatus($this->t('Submission deleted successfully.'));
    $form_state->setRedirectUrl(Url::fromRoute('custom_contact_form.submissions'));
  }

}
