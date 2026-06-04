<?php
$view = \Drupal::entityTypeManager()->getStorage('view')->load('commerce_user_orders');
$displays = $view->get('display');

// Fix uid field to render as raw entity ID (not username)
$displays['default']['display_options']['fields']['uid']['type'] = 'entity_reference_entity_id';
$displays['default']['display_options']['fields']['uid']['settings'] = [];

$view->set('display', $displays);
$view->save();
echo "Updated uid field formatter to entity_reference_entity_id.\n";

// Quick test render to see what tokens are available
$view2 = \Drupal\views\Views::getView('commerce_user_orders');
$view2->setDisplay('order_page');
$view2->execute();
if (!empty($view2->result)) {
  $row = $view2->result[0];
  $uid_val = $view2->field['uid']->advancedRender($row);
  $oid_val = $view2->field['order_id']->advancedRender($row);
  echo "Sample uid token value: $uid_val\n";
  echo "Sample order_id token value: $oid_val\n";
}
