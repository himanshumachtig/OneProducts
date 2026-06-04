<?php
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

$new_fields = [
  'field_refund_status' => [
    'type'    => 'list_string',
    'label'   => 'Refund Status',
    'storage_settings' => [
      'allowed_values' => [
        'none'      => 'None',
        'requested' => 'Refund Requested',
        'approved'  => 'Refund Approved',
        'rejected'  => 'Refund Rejected',
      ],
    ],
    'default_value' => [['value' => 'none']],
    'widget' => 'options_select',
    'weight' => 102,
  ],
  'field_refund_request' => [
    'type'    => 'text_long',
    'label'   => 'Refund Request — Customer Reason',
    'storage_settings' => [],
    'default_value' => [],
    'widget' => 'text_textarea',
    'weight' => 103,
  ],
  'field_refund_notes' => [
    'type'    => 'text_long',
    'label'   => 'Refund Notes — Admin Response',
    'storage_settings' => [],
    'default_value' => [],
    'widget' => 'text_textarea',
    'weight' => 104,
  ],
];

foreach ($new_fields as $field_name => $cfg) {
  if (!FieldStorageConfig::loadByName('commerce_order', $field_name)) {
    $storage = [
      'field_name'  => $field_name,
      'entity_type' => 'commerce_order',
      'type'        => $cfg['type'],
      'cardinality' => 1,
    ];
    if (!empty($cfg['storage_settings'])) {
      $storage['settings'] = $cfg['storage_settings'];
    }
    FieldStorageConfig::create($storage)->save();
    echo "Created storage: $field_name\n";
  } else {
    echo "Storage already exists: $field_name\n";
  }

  if (!FieldConfig::loadByName('commerce_order', 'default', $field_name)) {
    $field_def = [
      'field_name'  => $field_name,
      'entity_type' => 'commerce_order',
      'bundle'      => 'default',
      'label'       => $cfg['label'],
      'required'    => FALSE,
    ];
    if (!empty($cfg['default_value'])) {
      $field_def['default_value'] = $cfg['default_value'];
    }
    FieldConfig::create($field_def)->save();
    echo "Created field config: $field_name\n";
  } else {
    echo "Field config already exists: $field_name\n";
  }
}

// Add to order_details form display (what admin sees when editing an order)
foreach (['commerce_order.default.order_details', 'commerce_order.default.default'] as $display_id) {
  $fd = EntityFormDisplay::load($display_id);
  if (!$fd) continue;
  $fd->setComponent('field_refund_status', [
    'type' => 'options_select', 'weight' => 102, 'region' => 'content', 'settings' => [],
  ]);
  $fd->setComponent('field_refund_request', [
    'type' => 'text_textarea', 'weight' => 103, 'region' => 'content', 'settings' => ['rows' => 3],
  ]);
  $fd->setComponent('field_refund_notes', [
    'type' => 'text_textarea', 'weight' => 104, 'region' => 'content', 'settings' => ['rows' => 3],
  ]);
  $fd->save();
  echo "Updated form display: $display_id\n";
}

echo "\nAll done.\n";
