<?php
$view = \Drupal::entityTypeManager()->getStorage('view')->load('commerce_user_orders');
$displays = $view->get('display');

$fields = $displays['default']['display_options']['fields'];

// Desired order: uid and order_id (token sources) first, then display fields, then the link
$desired_order = ['order_id', 'uid', 'order_number', 'placed', 'total_price__number', 'state', 'nothing'];

$reordered = [];
foreach ($desired_order as $key) {
  if (isset($fields[$key])) {
    $reordered[$key] = $fields[$key];
  }
}
// Append any remaining fields not in desired_order
foreach ($fields as $key => $field) {
  if (!isset($reordered[$key])) {
    $reordered[$key] = $field;
  }
}

$displays['default']['display_options']['fields'] = $reordered;
$view->set('display', $displays);
$view->save();

echo "New field order: " . implode(', ', array_keys($reordered)) . PHP_EOL;
echo "View saved.\n";
