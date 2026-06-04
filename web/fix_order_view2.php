<?php
$view = \Drupal::entityTypeManager()->getStorage('view')->load('commerce_user_orders');
$displays = $view->get('display');

// Add hidden uid field to default display for use as token
if (!isset($displays['default']['display_options']['fields']['uid'])) {
  $displays['default']['display_options']['fields']['uid'] = [
    'id'           => 'uid',
    'table'        => 'commerce_order',
    'field'        => 'uid',
    'relationship' => 'none',
    'group_type'   => 'group',
    'admin_label'  => '',
    'label'        => 'Customer',
    'exclude'      => TRUE,
    'alter'        => [
      'alter_text'     => FALSE,
      'make_link'      => FALSE,
      'absolute'       => FALSE,
      'word_boundary'  => TRUE,
      'ellipsis'       => TRUE,
      'strip_tags'     => FALSE,
      'trim'           => FALSE,
      'nl2br'          => FALSE,
      'max_length'     => 0,
      'link_class'     => '',
      'prefix'         => '',
      'suffix'         => '',
      'target'         => '',
      'trim_whitespace'=> FALSE,
      'more_link'      => FALSE,
      'more_link_text' => '',
      'more_link_path' => '',
      'path'           => '',
      'replace_spaces' => FALSE,
      'path_case'      => 'none',
      'external'       => FALSE,
      'rel'            => '',
      'text'           => '',
      'alt'            => '',
      'html'           => FALSE,
      'preserve_tags'  => '',
      'empty_zero'     => FALSE,
      'hide_alter_empty'=> TRUE,
    ],
    'element_type'            => '',
    'element_class'           => '',
    'element_label_type'      => '',
    'element_label_class'     => '',
    'element_label_colon'     => TRUE,
    'element_wrapper_type'    => '',
    'element_wrapper_class'   => '',
    'element_default_classes' => TRUE,
    'empty'           => '',
    'hide_empty'      => FALSE,
    'empty_zero'      => FALSE,
    'hide_alter_empty'=> TRUE,
    'click_sort_column'=> 'target_id',
    'type'            => 'entity_reference_label',
    'settings'        => ['link' => FALSE],
    'group_column'    => 'target_id',
    'group_columns'   => [],
    'group_rows'      => TRUE,
    'delta_limit'     => 0,
    'delta_offset'    => 0,
    'delta_reversed'  => FALSE,
    'delta_first_last'=> FALSE,
    'multi_type'      => 'separator',
    'separator'       => ', ',
    'field_api_classes'=> FALSE,
    'plugin_id'       => 'field',
  ];
  echo "Added hidden uid field.\n";
}

// Update the nothing field text with correct token format
// In Views, field tokens use [field_id] syntax
$displays['default']['display_options']['fields']['nothing']['alter']['text'] =
  '<a href="/user/[uid]/orders/[order_id]" class="btn btn-sm btn-outline-primary rounded-pill px-3">'
  . '<i class="fas fa-eye me-1"></i>View</a>';

$view->set('display', $displays);
$view->save();
echo "View saved.\n";

$saved = \Drupal::entityTypeManager()->getStorage('view')->load('commerce_user_orders');
$d2 = $saved->get('display');
echo "default fields: " . implode(', ', array_keys($d2['default']['display_options']['fields'] ?? [])) . "\n";
echo "Token text: " . $d2['default']['display_options']['fields']['nothing']['alter']['text'] . "\n";
