<?php

namespace Drupal\custom_contact_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class EditSubmissionForm extends FormBase {

  public function getFormId() {
    return 'custom_contact_form_edit';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $record = \Drupal::database()->select('custom_contact_form_submissions', 's')
      ->fields('s')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$record) {
      $this->messenger()->addError($this->t('Submission not found.'));
      return $this->redirect('custom_contact_form.submissions');
    }

    $form['id'] = ['#type' => 'hidden', '#value' => $id];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $record->name,
      '#required' => TRUE,
      '#maxlength' => 100,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $record->email,
      '#required' => TRUE,
    ];

    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone'),
      '#default_value' => $record->phone,
      '#required' => TRUE,
      '#maxlength' => 20,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('custom_contact_form.submissions'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $phone = $form_state->getValue('phone');
    if (!preg_match('/^[0-9\+\-\(\)\s]+$/', $phone)) {
      $form_state->setErrorByName('phone', $this->t('Please enter a valid phone number.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::database()->update('custom_contact_form_submissions')
      ->fields([
        'name'  => $form_state->getValue('name'),
        'email' => $form_state->getValue('email'),
        'phone' => $form_state->getValue('phone'),
      ])
      ->condition('id', $form_state->getValue('id'))
      ->execute();

    $this->messenger()->addStatus($this->t('Submission updated successfully.'));
    $form_state->setRedirectUrl(Url::fromRoute('custom_contact_form.submissions'));
  }

}
