<?php

namespace Drupal\news_list\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

class NewsController extends ControllerBase {

  public function listNews() {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'news')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->execute();

    $nodes = Node::loadMultiple($nids);

    $build = [];
    foreach ($nodes as $node) {
      $build[] = \Drupal::entityTypeManager()
        ->getViewBuilder('node')
        ->view($node, 'teaser');
    }

    if (empty($build)) {
      $build[] = ['#markup' => $this->t('<p>No news found.</p>')];
    }

    return [
      '#theme_wrappers' => ['container'],
      '#attributes' => ['class' => ['news-listing']],
      'content' => $build,
      '#cache' => ['tags' => ['node_list']],
    ];
  }

}
