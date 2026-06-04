<?php

namespace Drupal\custom_contact_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ContactForm extends FormBase {

  public function getFormId() {
    return 'custom_contact_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
      '#maxlength' => 100,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone'),
      '#required' => TRUE,
      '#maxlength' => 20,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
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
    \Drupal::database()->insert('custom_contact_form_submissions')
      ->fields([
        'name'    => $form_state->getValue('name'),
        'email'   => $form_state->getValue('email'),
        'phone'   => $form_state->getValue('phone'),
        'created' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    $this->messenger()->addStatus($this->t('Thank you, @name! We will contact you at @email soon.', [
      '@name' => $form_state->getValue('name'),
      '@email' => $form_state->getValue('email'),
    ]));
  }

}
