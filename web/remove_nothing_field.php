<?php
$view = \Drupal::entityTypeManager()->getStorage('view')->load('commerce_user_orders');
$displays = $view->get('display');

// Remove nothing and uid fields - we'll add the link via preprocess hook instead
unset($displays['default']['display_options']['fields']['nothing']);
unset($displays['default']['display_options']['fields']['uid']);
// Also un-exclude order_id so it can be used directly
// (keep it excluded from display, just available for the preprocess hook via result)

$view->set('display', $displays);
$view->save();

$d = $view->get('display');
echo "Fields: " . implode(', ', array_keys($d['default']['display_options']['fields'])) . PHP_EOL;
echo "Done.\n";
