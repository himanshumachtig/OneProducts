<?php

namespace Drupal\custom_contact_form\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

class SubmissionsController extends ControllerBase {

  public function listSubmissions() {
    $rows = [];
    $results = \Drupal::database()->select('custom_contact_form_submissions', 's')
      ->fields('s', ['id', 'name', 'email', 'phone', 'created'])
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchAll();

    foreach ($results as $row) {
      $edit_link = Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('custom_contact_form.edit', ['id' => $row->id]))->toRenderable();
      $edit_link['#attributes'] = ['class' => ['button', 'button--small']];

      $delete_link = Link::fromTextAndUrl($this->t('Delete'), Url::fromRoute('custom_contact_form.delete', ['id' => $row->id]))->toRenderable();
      $delete_link['#attributes'] = ['class' => ['button', 'button--small', 'button--danger']];

      $rows[] = [
        $row->id,
        $row->name,
        $row->email,
        $row->phone,
        \Drupal::service('date.formatter')->format($row->created, 'short'),
        ['data' => ['#type' => 'operations', '#links' => [
          'edit'   => ['title' => $this->t('Edit'),   'url' => Url::fromRoute('custom_contact_form.edit',   ['id' => $row->id])],
          'delete' => ['title' => $this->t('Delete'), 'url' => Url::fromRoute('custom_contact_form.delete', ['id' => $row->id])],
        ]]],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => ['#', $this->t('Name'), $this->t('Email'), $this->t('Phone'), $this->t('Submitted'), $this->t('Actions')],
      '#rows' => $rows,
      '#empty' => $this->t('No submissions yet.'),
    ];
  }

}
