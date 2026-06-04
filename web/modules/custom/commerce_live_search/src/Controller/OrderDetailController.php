<?php

namespace Drupal\commerce_live_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Frontend order detail — view, cancel, and refund requests.
 */
class OrderDetailController extends ControllerBase {

  public function detail(OrderInterface $commerce_order): array {
    $order = $commerce_order;

    // ── Order items ──────────────────────────────────────────────────────────
    $current_uid = \Drupal::currentUser()->id();
    $items = [];
    foreach ($order->getItems() as $item) {
      $variation  = $item->getPurchasedEntity();
      $product    = $variation ? $variation->getProduct() : NULL;
      $product_id = $product ? (int) $product->id() : 0;
      $image_url  = '';
      if ($product && $product->hasField('field_product_image')
          && !$product->get('field_product_image')->isEmpty()) {
        $file = $product->get('field_product_image')->entity;
        if ($file) {
          $image_url = \Drupal::service('file_url_generator')
            ->generateAbsoluteString($file->getFileUri());
        }
      }
      // Check if user already reviewed this product.
      $user_rating      = 0;
      $user_review_text = '';
      if ($product_id && $current_uid) {
        $existing = \Drupal::entityTypeManager()->getStorage('comment')->loadByProperties([
          'comment_type' => 'product_review',
          'entity_id'    => $product_id,
          'uid'          => $current_uid,
          'status'       => 1,
        ]);
        if ($existing) {
          $c = reset($existing);
          if ($c->hasField('field_rating') && !$c->get('field_rating')->isEmpty()) {
            $user_rating = (int) $c->get('field_rating')->value;
          }
          if ($c->hasField('comment_body') && !$c->get('comment_body')->isEmpty()) {
            $user_review_text = $c->get('comment_body')->value;
          }
        }
      }
      $items[] = [
        'title'            => $item->getTitle(),
        'quantity'         => (int) $item->getQuantity(),
        'unit_price'       => '$' . number_format($item->getUnitPrice()->getNumber(), 2),
        'total_price'      => '$' . number_format($item->getTotalPrice()->getNumber(), 2),
        'image'            => $image_url,
        'url'              => $product ? $product->toUrl()->toString() : '',
        'product_id'       => $product_id,
        'user_rating'      => $user_rating,
        'user_review_text' => $user_review_text,
      ];
    }

    // ── Payment status ───────────────────────────────────────────────────────
    $payments = \Drupal::entityTypeManager()
      ->getStorage('commerce_payment')
      ->loadByProperties(['order_id' => $order->id()]);
    $payment_status = 'unpaid';
    $payment_label  = 'Awaiting Payment';
    $payment_time   = NULL;
    if ($payments) {
      $payment        = reset($payments);
      $payment_status = $payment->getState()->getId();
      $payment_label  = (string) $payment->getState()->getLabel();
      if (method_exists($payment, 'getCompletedTime')) {
        $payment_time = $payment->getCompletedTime();
      }
    }
    $payment_gateway_label = '';
    if (!$order->get('payment_gateway')->isEmpty()) {
      $payment_gateway_label = $order->get('payment_gateway')->entity->label();
    }

    // ── Delivery status ──────────────────────────────────────────────────────
    $delivery_status = 'pending';
    $delivery_label  = 'Pending';
    if ($order->hasField('field_delivery_status')
        && !$order->get('field_delivery_status')->isEmpty()) {
      $delivery_status = $order->get('field_delivery_status')->value;
      $map = [
        'pending'    => 'Pending',    'processing' => 'Processing',
        'shipped'    => 'Shipped',    'delivered'  => 'Delivered',
        'cancelled'  => 'Cancelled',  'refunded'   => 'Refunded',
      ];
      $delivery_label = $map[$delivery_status] ?? ucfirst($delivery_status);
    }

    // ── Refund fields ────────────────────────────────────────────────────────
    $refund_status  = 'none';
    $refund_request = '';
    $refund_notes   = '';
    if ($order->hasField('field_refund_status')
        && !$order->get('field_refund_status')->isEmpty()) {
      $refund_status = $order->get('field_refund_status')->value;
    }
    if ($order->hasField('field_refund_request')
        && !$order->get('field_refund_request')->isEmpty()) {
      $refund_request = $order->get('field_refund_request')->value;
    }
    if ($order->hasField('field_refund_notes')
        && !$order->get('field_refund_notes')->isEmpty()) {
      $refund_notes = $order->get('field_refund_notes')->value;
    }

    // ── Admin notes ──────────────────────────────────────────────────────────
    $admin_notes = '';
    if ($order->hasField('field_admin_notes')
        && !$order->get('field_admin_notes')->isEmpty()) {
      $admin_notes = $order->get('field_admin_notes')->value;
    }

    // ── Expected delivery date ────────────────────────────────────────────────
    $expected_delivery_date = '';
    if ($order->hasField('field_expected_delivery_date')
        && !$order->get('field_expected_delivery_date')->isEmpty()) {
      $date_value = $order->get('field_expected_delivery_date')->value;
      if ($date_value) {
        $expected_delivery_date = date('d M Y', strtotime($date_value));
      }
    }

    // ── Billing address ──────────────────────────────────────────────────────
    $billing = [];
    $profile = $order->getBillingProfile();
    if ($profile && !$profile->get('address')->isEmpty()) {
      $addr    = $profile->get('address')->first();
      $billing = [
        'name'     => trim($addr->getGivenName() . ' ' . $addr->getFamilyName()),
        'line1'    => $addr->getAddressLine1(),
        'line2'    => $addr->getAddressLine2(),
        'city'     => $addr->getLocality(),
        'state'    => $addr->getAdministrativeArea(),
        'postcode' => $addr->getPostalCode(),
        'country'  => $addr->getCountryCode(),
      ];
    }

    // ── Dynamic timeline ─────────────────────────────────────────────────────
    $timeline = $this->buildTimeline(
      $order, $payment_status, $payment_label,
      $payment_time, $delivery_status, $refund_status
    );

    // ── Action eligibility ───────────────────────────────────────────────────
    // Cancel: only before shipping and only if not already cancelled/refunded.
    $can_cancel = in_array($delivery_status, ['pending', 'processing'])
      && !in_array($delivery_status, ['cancelled', 'refunded'])
      && $payment_status !== 'refunded';

    // Refund: only after delivery + payment received + no existing request.
    $payment_received = in_array($payment_status, ['completed', 'authorized']);
    $can_refund = $delivery_status === 'delivered'
      && $payment_received
      && $refund_status === 'none';

    // Determine if rating section should show (delivered orders only).
    $can_rate = ($delivery_status === 'delivered');

    // CSRF tokens for AJAX actions.
    $cancel_token = \Drupal::csrfToken()->get('order-cancel-' . $order->id());
    $refund_token  = \Drupal::csrfToken()->get('order-refund-' . $order->id());
    $rating_token  = \Drupal::csrfToken()->get('order-rate-' . $order->id());

    return [
      '#theme'             => 'order_detail_page',
      '#order'             => $order,
      '#items'             => $items,
      '#billing'           => $billing,
      '#timeline'          => $timeline,
      '#payment_status'    => $payment_status,
      '#payment_label'     => $payment_label,
      '#payment_gateway'   => $payment_gateway_label,
      '#delivery_status'   => $delivery_status,
      '#delivery_label'    => $delivery_label,
      '#admin_notes'             => $admin_notes,
      '#expected_delivery_date'  => $expected_delivery_date,
      '#refund_status'     => $refund_status,
      '#refund_request'    => $refund_request,
      '#refund_notes'      => $refund_notes,
      '#can_cancel'        => $can_cancel,
      '#can_refund'        => $can_refund,
      '#can_rate'          => $can_rate,
      '#total'             => '$' . number_format($order->getTotalPrice()->getNumber(), 2),
      '#total_paid'        => '$' . number_format($order->getTotalPaid()->getNumber(), 2),
      '#placed_date'       => $order->getPlacedTime() ? date('d M Y, h:i A', $order->getPlacedTime()) : '',
      '#order_state'       => $order->getState()->getId(),
      '#order_state_label' => $order->getState()->getLabel(),
      '#attached'          => [
        'library'        => ['commerce_theme/order-actions'],
        'drupalSettings' => [
          'orderActions' => [
            'orderId'     => $order->id(),
            'cancelUrl'   => '/orders/' . $order->id() . '/cancel',
            'refundUrl'   => '/orders/' . $order->id() . '/refund-request',
            'cancelToken' => $cancel_token,
            'refundToken' => $refund_token,
            'ratingToken' => $rating_token,
            'ratingUrl'   => '/orders/' . $order->id() . '/rate-product',
          ],
        ],
      ],
      '#cache' => [
        'tags'     => $order->getCacheTags(),
        'contexts' => ['user'],
      ],
    ];
  }

  // ── Timeline builder ─────────────────────────────────────────────────────────
  // Status values: done | active | future | error
  // done   = green filled     — step completed
  // active = orange + pulse   — step currently in progress
  // future = grey dashed      — not yet reached
  // error  = red filled       — cancelled / rejected

  protected function buildTimeline(
    OrderInterface $order,
    string $payment_status,
    string $payment_label,
    $payment_time,
    string $delivery_status,
    string $refund_status
  ): array {

    // payment_received: payment was completed (may have been refunded later).
    $payment_received = in_array($payment_status, ['completed', 'authorized', 'refunded']);
    // payment_active:   payment is currently confirmed and not yet refunded.
    $payment_active   = in_array($payment_status, ['completed', 'authorized']);
    $payment_refunded = ($payment_status === 'refunded');

    $level = match ($delivery_status) {
      'processing' => 1,
      'shipped'    => 2,
      'delivered'  => 3,
      'cancelled'  => -1,
      'refunded'   => -2,
      default      => 0,
    };

    $steps = [];

    // ── Step 1: Order Placed — always done ─────────────────────────────────
    $steps[] = [
      'label'  => 'Order Placed',
      'status' => 'done',
      'icon'   => 'fa-shopping-bag',
      'time'   => $order->getPlacedTime(),
    ];

    // ── Step 2: Payment ────────────────────────────────────────────────────
    if ($payment_received) {
      // Payment was received (even if later refunded — it was received).
      $steps[] = [
        'label'  => 'Payment Received',
        'status' => 'done',
        'icon'   => 'fa-credit-card',
        'time'   => $payment_time,
      ];
    } elseif ($level < 0) {
      // Order cancelled/refunded before payment.
      $steps[] = [
        'label'  => 'Payment ' . $payment_label,
        'status' => 'error',
        'icon'   => 'fa-credit-card',
        'time'   => NULL,
      ];
    } else {
      // Payment not yet received — current active step.
      $steps[] = [
        'label'  => 'Awaiting Payment',
        'status' => 'active',
        'icon'   => 'fa-credit-card',
        'time'   => NULL,
      ];
    }

    // ── Cancelled branch ───────────────────────────────────────────────────
    if ($level === -1) {
      $steps[] = [
        'label'  => 'Order Cancelled',
        'status' => 'error',
        'icon'   => 'fa-times-circle',
        'time'   => $order->getChangedTime(),
      ];
      return $steps;
    }

    // ── Refunded-via-delivery branch ───────────────────────────────────────
    if ($level === -2) {
      $steps[] = [
        'label'  => 'Refunded',
        'status' => 'error',
        'icon'   => 'fa-undo',
        'time'   => $order->getChangedTime(),
      ];
      return $steps;
    }

    // ── Step 3: Processing ─────────────────────────────────────────────────
    // Use payment_received (not payment_active) so refunded orders still show
    // delivery steps as completed.
    if ($level >= 1) {
      $steps[] = ['label' => 'Processing', 'status' => 'done', 'icon' => 'fa-cog', 'time' => NULL];
    } elseif ($payment_received) {
      $steps[] = ['label' => 'Processing', 'status' => 'active', 'icon' => 'fa-cog fa-spin', 'time' => NULL];
    } else {
      $steps[] = ['label' => 'Processing', 'status' => 'future', 'icon' => 'fa-cog', 'time' => NULL];
    }

    // ── Step 4: Shipped ────────────────────────────────────────────────────
    if ($level >= 2) {
      $steps[] = ['label' => 'Shipped', 'status' => 'done', 'icon' => 'fa-truck', 'time' => NULL];
    } elseif ($level >= 1) {
      $steps[] = ['label' => 'Shipped', 'status' => 'active', 'icon' => 'fa-truck', 'time' => NULL];
    } else {
      $steps[] = ['label' => 'Shipped', 'status' => 'future', 'icon' => 'fa-truck', 'time' => NULL];
    }

    // ── Step 5: Delivered ──────────────────────────────────────────────────
    if ($level >= 3) {
      $steps[] = ['label' => 'Delivered', 'status' => 'done', 'icon' => 'fa-check-circle', 'time' => NULL];
    } elseif ($level >= 2) {
      $steps[] = ['label' => 'Delivered', 'status' => 'active', 'icon' => 'fa-check-circle', 'time' => NULL];
    } else {
      $steps[] = ['label' => 'Delivered', 'status' => 'future', 'icon' => 'fa-check-circle', 'time' => NULL];
    }

    // ── Step 6: Refund ─────────────────────────────────────────────────────
    // Rule: "Refund Processed" shows ONLY when Commerce payment_status = 'refunded'.
    //       field_refund_status tracks the request workflow independently.
    //       If payment was re-received (completed) after a refund, no refund step shows.
    if ($payment_refunded) {
      $steps[] = [
        'label'  => 'Refund Processed',
        'status' => 'done',
        'icon'   => 'fa-undo',
        'time'   => $order->getChangedTime(),
      ];
    } elseif ($refund_status === 'requested') {
      $steps[] = [
        'label'  => 'Refund Requested',
        'status' => 'active',
        'icon'   => 'fa-undo',
        'time'   => NULL,
      ];
    } elseif ($refund_status === 'approved') {
      $steps[] = [
        'label'  => 'Refund Approved',
        'status' => 'done',
        'icon'   => 'fa-check-circle',
        'time'   => $order->getChangedTime(),
      ];
    } elseif ($refund_status === 'rejected') {
      $steps[] = [
        'label'  => 'Refund Rejected',
        'status' => 'error',
        'icon'   => 'fa-ban',
        'time'   => NULL,
      ];
    }

    return $steps;
  }

  // ── Cancel order ─────────────────────────────────────────────────────────────

  public function cancelOrder(OrderInterface $commerce_order, Request $request): JsonResponse {
    $account = \Drupal::currentUser();

    $token = $request->request->get('csrf_token')
           ?? $request->headers->get('X-CSRF-Token', '');
    if (!\Drupal::csrfToken()->validate($token, 'order-cancel-' . $commerce_order->id())) {
      return new JsonResponse(['error' => 'Security check failed.'], 403);
    }
    if (!$account->hasPermission('administer commerce_order')
        && (int) $commerce_order->getCustomerId() !== (int) $account->id()) {
      return new JsonResponse(['error' => 'Access denied.'], 403);
    }

    $delivery = $commerce_order->hasField('field_delivery_status')
      ? ($commerce_order->get('field_delivery_status')->value ?? 'pending')
      : 'pending';

    if (!in_array($delivery, ['pending', 'processing'])) {
      return new JsonResponse(['error' => 'Order cannot be cancelled after shipping.'], 400);
    }

    // Cancel via state machine if transition available, otherwise force-set state.
    $state_item  = $commerce_order->getState();
    $transitions = $state_item->getTransitions();
    if (isset($transitions['cancel'])) {
      $state_item->applyTransition($transitions['cancel']);
    } else {
      $commerce_order->set('state', 'cancelled');
    }

    $commerce_order->set('field_delivery_status', 'cancelled');
    $commerce_order->save();

    return new JsonResponse(['success' => TRUE, 'message' => 'Order cancelled successfully.']);
  }

  // ── Refund request ───────────────────────────────────────────────────────────

  public function requestRefund(OrderInterface $commerce_order, Request $request): JsonResponse {
    $account = \Drupal::currentUser();

    $token = $request->request->get('csrf_token')
           ?? $request->headers->get('X-CSRF-Token', '');
    if (!\Drupal::csrfToken()->validate($token, 'order-refund-' . $commerce_order->id())) {
      return new JsonResponse(['error' => 'Security check failed.'], 403);
    }
    if (!$account->hasPermission('administer commerce_order')
        && (int) $commerce_order->getCustomerId() !== (int) $account->id()) {
      return new JsonResponse(['error' => 'Access denied.'], 403);
    }

    $delivery = $commerce_order->hasField('field_delivery_status')
      ? ($commerce_order->get('field_delivery_status')->value ?? '')
      : '';
    $refund_status = $commerce_order->hasField('field_refund_status')
      ? ($commerce_order->get('field_refund_status')->value ?? 'none')
      : 'none';

    if ($delivery !== 'delivered') {
      return new JsonResponse(['error' => 'Refund can only be requested after delivery.'], 400);
    }
    if ($refund_status !== 'none') {
      return new JsonResponse(['error' => 'A refund request already exists.'], 400);
    }

    $reason = trim($request->request->get('reason', ''));
    if (empty($reason)) {
      return new JsonResponse(['error' => 'Please provide a reason for your refund request.'], 400);
    }

    $commerce_order->set('field_refund_status', 'requested');
    if ($commerce_order->hasField('field_refund_request')) {
      $commerce_order->set('field_refund_request', $reason);
    }
    $commerce_order->save();

    return new JsonResponse(['success' => TRUE, 'message' => 'Refund request submitted. We will review it shortly.']);
  }

  // ── Rate product from order page ─────────────────────────────────────────────

  public function rateProduct(OrderInterface $commerce_order, Request $request): JsonResponse {
    $account = \Drupal::currentUser();

    $token = $request->request->get('csrf_token')
           ?? $request->headers->get('X-CSRF-Token', '');
    if (!\Drupal::csrfToken()->validate($token, 'order-rate-' . $commerce_order->id())) {
      return new JsonResponse(['error' => 'Security check failed.'], 403);
    }
    if (!$account->isAuthenticated()
        || (int) $commerce_order->getCustomerId() !== (int) $account->id()) {
      return new JsonResponse(['error' => 'Access denied.'], 403);
    }

    $delivery = $commerce_order->hasField('field_delivery_status')
      ? ($commerce_order->get('field_delivery_status')->value ?? '')
      : '';
    if ($delivery !== 'delivered') {
      return new JsonResponse(['error' => 'You can only rate delivered orders.'], 400);
    }

    $product_id  = (int) $request->request->get('product_id');
    $rating      = (int) $request->request->get('rating');
    $review_text = trim((string) $request->request->get('review_text', ''));

    if ($rating < 1 || $rating > 5 || !$product_id) {
      return new JsonResponse(['error' => 'Invalid rating or product.'], 400);
    }

    $product = \Drupal::entityTypeManager()
      ->getStorage('commerce_product')
      ->load($product_id);
    if (!$product || !$product->hasField('field_product_reviews')) {
      return new JsonResponse(['error' => 'Product not found.'], 404);
    }

    $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');

    // Update existing review if any, otherwise create a new one.
    $existing = $comment_storage->loadByProperties([
      'comment_type' => 'product_review',
      'entity_id'    => $product_id,
      'uid'          => $account->id(),
    ]);

    if ($existing) {
      $comment = reset($existing);
      $comment->set('field_rating', $rating);
      if ($review_text !== '' && $comment->hasField('comment_body')) {
        $comment->set('comment_body', ['value' => $review_text, 'format' => 'plain_text']);
      }
      $comment->set('status', 1);
      $comment->save();
    } else {
      $comment = $comment_storage->create([
        'comment_type' => 'product_review',
        'entity_type'  => 'commerce_product',
        'entity_id'    => $product_id,
        'field_name'   => 'field_product_reviews',
        'uid'          => $account->id(),
        'subject'      => 'Order review',
        'field_rating' => $rating,
        'status'       => 1,
      ]);
      if ($review_text !== '' && $comment->hasField('comment_body')) {
        $comment->set('comment_body', ['value' => $review_text, 'format' => 'plain_text']);
      }
      $comment->save();
    }

    // Recalculate average for response.
    $all = $comment_storage->loadByProperties([
      'comment_type' => 'product_review',
      'entity_id'    => $product_id,
      'status'       => 1,
    ]);
    $total = array_sum(array_map(fn($c) => (int) $c->get('field_rating')->value, $all));
    $avg   = count($all) ? round($total / count($all), 1) : 0;

    return new JsonResponse([
      'success'   => TRUE,
      'rating'    => $rating,
      'avg'       => $avg,
      'count'     => count($all),
      'message'   => 'Rating saved!',
    ]);
  }

  // ── Access check ─────────────────────────────────────────────────────────────

  public function access(AccountInterface $account, OrderInterface $commerce_order): AccessResult {
    if ($account->hasPermission('administer commerce_order')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    $is_owner = $account->isAuthenticated()
      && (int) $commerce_order->getCustomerId() === (int) $account->id();
    return AccessResult::allowedIf($is_owner)
      ->cachePerUser()
      ->addCacheableDependency($commerce_order);
  }

}
