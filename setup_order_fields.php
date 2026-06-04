<?php
/**
 * Creates custom fields on commerce_order for delivery status and admin notes.
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

// ── 1. field_delivery_status (list_string) ───────────────────────────────────
if (!FieldStorageConfig::loadByName('commerce_order', 'field_delivery_status')) {
  FieldStorageConfig::create([
    'field_name'  => 'field_delivery_status',
    'entity_type' => 'commerce_order',
    'type'        => 'list_string',
    'settings'    => [
      'allowed_values' => [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'shipped'    => 'Shipped',
        'delivered'  => 'Delivered',
        'cancelled'  => 'Cancelled',
        'refunded'   => 'Refunded',
      ],
    ],
    'cardinality' => 1,
  ])->save();
  echo "Created field_delivery_status storage\n";
}

if (!FieldConfig::loadByName('commerce_order', 'default', 'field_delivery_status')) {
  FieldConfig::create([
    'field_name'    => 'field_delivery_status',
    'entity_type'   => 'commerce_order',
    'bundle'        => 'default',
    'label'         => 'Delivery Status',
    'default_value' => [['value' => 'pending']],
    'required'      => FALSE,
  ])->save();
  echo "Attached field_delivery_status to order bundle\n";
}

// ── 2. field_admin_notes (text_long) ─────────────────────────────────────────
if (!FieldStorageConfig::loadByName('commerce_order', 'field_admin_notes')) {
  FieldStorageConfig::create([
    'field_name'  => 'field_admin_notes',
    'entity_type' => 'commerce_order',
    'type'        => 'text_long',
    'cardinality' => 1,
  ])->save();
  echo "Created field_admin_notes storage\n";
}

if (!FieldConfig::loadByName('commerce_order', 'default', 'field_admin_notes')) {
  FieldConfig::create([
    'field_name'  => 'field_admin_notes',
    'entity_type' => 'commerce_order',
    'bundle'      => 'default',
    'label'       => 'Order Notes (visible to customer)',
    'required'    => FALSE,
  ])->save();
  echo "Attached field_admin_notes to order bundle\n";
}

// ── 3. Add fields to admin order form display ─────────────────────────────────
$form_display = EntityFormDisplay::load('commerce_order.default.default');
if (!$form_display) {
  $form_display = EntityFormDisplay::create([
    'targetEntityType' => 'commerce_order',
    'bundle'           => 'default',
    'mode'             => 'default',
    'status'           => TRUE,
  ]);
}
if (!$form_display->getComponent('field_delivery_status')) {
  $form_display->setComponent('field_delivery_status', [
    'type'     => 'options_select',
    'weight'   => 100,
    'settings' => [],
  ]);
}
if (!$form_display->getComponent('field_admin_notes')) {
  $form_display->setComponent('field_admin_notes', [
    'type'     => 'text_textarea',
    'weight'   => 101,
    'settings' => ['rows' => 4],
  ]);
}
$form_display->save();
echo "Updated admin order form display\n";

// ── 4. Set the delivery status on the existing sample order ──────────────────
$order = \Drupal::entityTypeManager()->getStorage('commerce_order')->load(1);
if ($order) {
  if ($order->hasField('field_delivery_status') && $order->get('field_delivery_status')->isEmpty()) {
    $order->set('field_delivery_status', 'delivered');
    $order->set('field_admin_notes', 'Your order has been delivered successfully. Thank you for shopping with us!');
    $order->save();
    echo "Updated order #1 with sample delivery status\n";
  }
}

echo "\n✓ Done! Fields created.\n";
echo "  Admin can now set Delivery Status and Order Notes at:\n";
echo "  Admin → Commerce → Orders → Edit order\n";
