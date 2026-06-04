<?php

namespace Drupal\test_content_list\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

class TestContentController extends ControllerBase {

  public function listContent() {
    $items = [];

    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'test')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->execute();

    $nodes = Node::loadMultiple($nids);

    foreach ($nodes as $node) {
      $image_url = NULL;
      if ($node->hasField('field_image') && !$node->get('field_image')->isEmpty()) {
        $file = $node->get('field_image')->entity;
        if ($file) {
          $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
          $image_alt = $node->get('field_image')->alt ?? '';
        }
      }

      $items[] = [
        'nid'         => $node->id(),
        'title'       => $node->label(),
        'field_title' => $node->hasField('field_title') ? $node->get('field_title')->value : '',
        'field_body'  => $node->hasField('field_body')  ? check_markup($node->get('field_body')->value, $node->get('field_body')->format ?? 'basic_html') : '',
        'image_url'   => $image_url,
        'image_alt'   => $image_alt ?? '',
        'url'         => $node->toUrl()->toString(),
      ];
    }

    return [
      '#theme'    => 'test_content_list_page',
      '#items'    => $items,
      '#cache'    => ['tags' => ['node_list']],
    ];
  }

}
