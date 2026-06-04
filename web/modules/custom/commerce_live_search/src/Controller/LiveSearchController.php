<?php

namespace Drupal\commerce_live_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class LiveSearchController extends ControllerBase {

  public function search(Request $request): JsonResponse {
    $query = trim($request->query->get('q', ''));

    if (strlen($query) < 2) {
      return new JsonResponse([]);
    }

    $db   = \Drupal::database();
    $like = '%' . $db->escapeLike($query) . '%';

    // Search across title, body, tagline, SKU, and category via direct DB.
    $select = $db->select('commerce_product_field_data', 'p')
      ->distinct()
      ->fields('p', ['product_id'])
      ->condition('p.status', 1);

    $select->leftJoin('commerce_product__body', 'pb',
      'pb.entity_id = p.product_id AND pb.deleted = 0');
    $select->leftJoin('commerce_product__field_tagline', 'ptl',
      'ptl.entity_id = p.product_id AND ptl.deleted = 0');
    $select->leftJoin('commerce_product__variations', 'pvar',
      'pvar.entity_id = p.product_id AND pvar.deleted = 0');
    $select->leftJoin('commerce_product_variation_field_data', 'pvd',
      'pvd.variation_id = pvar.variations_target_id AND pvd.status = 1');
    $select->leftJoin('commerce_product__field_category', 'pfc',
      'pfc.entity_id = p.product_id AND pfc.deleted = 0');
    $select->leftJoin('taxonomy_term_field_data', 'ptt',
      'ptt.tid = pfc.field_category_target_id');

    $or = $select->orConditionGroup()
      ->condition('p.title',                  $like, 'LIKE')
      ->condition('pb.body_value',             $like, 'LIKE')
      ->condition('pb.body_summary',           $like, 'LIKE')
      ->condition('ptl.field_tagline_value',   $like, 'LIKE')
      ->condition('pvd.sku',                   $like, 'LIKE')
      ->condition('ptt.name',                  $like, 'LIKE');

    $select->condition($or);
    $select->range(0, 8);

    $ids = $select->execute()->fetchCol();

    if (empty($ids)) {
      return new JsonResponse([]);
    }

    $storage           = $this->entityTypeManager()->getStorage('commerce_product');
    $file_url_generator = \Drupal::service('file_url_generator');
    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');

    $results = [];
    foreach ($storage->loadMultiple($ids) as $product) {
      $variation = $product->getDefaultVariation();
      $price     = $variation ? $variation->getPrice() : NULL;

      $img_url = '';
      if ($product->hasField('field_product_image') && !$product->get('field_product_image')->isEmpty()) {
        $file = $product->get('field_product_image')->entity;
        if ($file) {
          $img_url = $file_url_generator->generateAbsoluteString($file->getFileUri());
        }
      }

      $category = '';
      if ($product->hasField('field_category') && !$product->get('field_category')->isEmpty()) {
        $term = $product->get('field_category')->entity;
        if ($term) {
          $category = $term->label();
        }
      }

      $results[] = [
        'title'    => $product->getTitle(),
        'url'      => $product->toUrl('canonical')->toString(),
        'image'    => $img_url,
        'price'    => $price ? $currency_formatter->format($price->getNumber(), $price->getCurrencyCode()) : '',
        'category' => $category,
      ];
    }

    $response = new JsonResponse($results);
    $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    return $response;
  }

  /**
   * Redirects /user/orders to the current user's Commerce orders page.
   */
  public function userOrders(): RedirectResponse {
    $uid = $this->currentUser()->id();
    return new RedirectResponse("/user/{$uid}/orders");
  }

  /**
   * Checks whether an email address is registered.
   * GET /api/check-email?email=foo@bar.com
   * Returns JSON: { exists: true|false }
   */
  public function checkEmail(Request $request): JsonResponse {
    $email = trim($request->query->get('email', ''));

    if (empty($email) || !\Drupal::service('email.validator')->isValid($email)) {
      return new JsonResponse(['exists' => FALSE, 'valid' => FALSE]);
    }

    $uid = \Drupal::database()
      ->select('users_field_data', 'u')
      ->fields('u', ['uid'])
      ->condition('u.mail', $email)
      ->condition('u.status', 1)
      ->range(0, 1)
      ->execute()
      ->fetchField();

    return new JsonResponse(['exists' => (bool) $uid, 'valid' => TRUE]);
  }

}
