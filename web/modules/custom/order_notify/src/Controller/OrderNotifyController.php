<?php

namespace Drupal\order_notify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * AJAX endpoints for the order notification bell.
 */
class OrderNotifyController extends ControllerBase {

  /**
   * Returns unread order count + details as JSON.
   * Excludes individually dismissed orders.
   */
  public function count(): JsonResponse {
    $tempstore  = \Drupal::service('tempstore.private')->get('order_notify');
    $last_read  = $tempstore->get('last_read') ?? (\Drupal::time()->getRequestTime() - 7 * 86400);
    $dismissed  = $tempstore->get('dismissed_orders') ?? [];

    $storage = $this->entityTypeManager()->getStorage('commerce_order');
    $query   = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('state', ['draft', 'cancelled'], 'NOT IN')
      ->condition('placed', 0, '>')
      ->condition('placed', $last_read, '>')
      ->sort('placed', 'DESC')
      ->range(0, 20);

    // Exclude individually dismissed orders.
    if (!empty($dismissed)) {
      $query->condition('order_id', $dismissed, 'NOT IN');
    }

    $order_ids = $query->execute();

    $orders = [];
    foreach ($storage->loadMultiple($order_ids) as $order) {
      $customer = $order->getCustomer();
      $name     = (!$customer || $customer->isAnonymous())
        ? ($order->getEmail() ?: (string) $this->t('Guest'))
        : $customer->getDisplayName();

      $price    = $order->getTotalPrice();
      $orders[] = [
        'id'       => $order->id(),
        'number'   => $order->getOrderNumber() ?: $order->id(),
        'customer' => $name,
        'date'     => \Drupal::service('date.formatter')
          ->format((int) $order->getPlacedTime(), 'short'),
        'total'    => $price ? number_format((float) $price->getNumber(), 2) : '0.00',
        'currency' => $price ? $price->getCurrencyCode() : '',
        'url'      => \Drupal\Core\Url::fromRoute(
            'entity.commerce_order.canonical',
            ['commerce_order' => $order->id()]
          )->toString(),
      ];
    }

    return new JsonResponse([
      'count'  => count($orders),
      'orders' => $orders,
    ]);
  }

  /**
   * Dismiss a single order notification by ID.
   * Body: { "order_id": 14 }
   */
  public function dismiss(Request $request): JsonResponse {
    $data     = json_decode($request->getContent(), TRUE);
    $order_id = (int) ($data['order_id'] ?? 0);

    if (!$order_id) {
      return new JsonResponse(['error' => 'Invalid order_id'], 400);
    }

    $tempstore = \Drupal::service('tempstore.private')->get('order_notify');
    $dismissed = $tempstore->get('dismissed_orders') ?? [];

    if (!in_array($order_id, $dismissed)) {
      $dismissed[] = $order_id;
      $tempstore->set('dismissed_orders', $dismissed);
    }

    return new JsonResponse(['status' => 'ok', 'dismissed' => $order_id]);
  }

  /**
   * Mark all orders read — saves timestamp and clears dismissed list.
   */
  public function markRead(): JsonResponse {
    $tempstore = \Drupal::service('tempstore.private')->get('order_notify');
    $tempstore->set('last_read', \Drupal::time()->getRequestTime());
    $tempstore->delete('dismissed_orders');
    return new JsonResponse(['status' => 'ok']);
  }

}
