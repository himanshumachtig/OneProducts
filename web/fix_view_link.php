<?php
$view = \Drupal::entityTypeManager()->getStorage('view')->load('commerce_user_orders');
$displays = $view->get('display');

// Update the nothing field text to use simple /orders/[order_id] URL
$displays['default']['display_options']['fields']['nothing']['alter']['text'] =
  '<a href="/orders/[order_id]" class="btn btn-sm btn-outline-primary rounded-pill px-3">'
  . '<i class="fas fa-eye me-1"></i>View</a>';

// Remove the uid field (no longer needed as token)
unset($displays['default']['display_options']['fields']['uid']);

// Also make sure order_id comes before nothing
$fields = $displays['default']['display_options']['fields'];
$reordered = [];
foreach (['order_id', 'order_number', 'placed', 'total_price__number', 'state', 'nothing'] as $key) {
  if (isset($fields[$key])) {
    $reordered[$key] = $fields[$key];
  }
}
foreach ($fields as $key => $val) {
  if (!isset($reordered[$key])) $reordered[$key] = $val;
}
$displays['default']['display_options']['fields'] = $reordered;

$view->set('display', $displays);
$view->save();

echo "Fields: " . implode(', ', array_keys($reordered)) . PHP_EOL;
echo "Link text: " . $displays['default']['display_options']['fields']['nothing']['alter']['text'] . PHP_EOL;
echo "Done.\n";
