<?php
$view = \Drupal::entityTypeManager()->getStorage('view')->load('commerce_user_orders');

$displays = $view->get('display');

// 1. Remove the fields override from order_page display (let it inherit default)
if (isset($displays['order_page']['display_options']['fields'])) {
  unset($displays['order_page']['display_options']['fields']);
  echo "Removed fields override from order_page display.\n";
}

// 2. Add the 'View' link + uid hidden field to the DEFAULT display
if (!isset($displays['default']['display_options']['fields']['nothing'])) {
  $displays['default']['display_options']['fields']['nothing'] = [
    'id'           => 'nothing',
    'table'        => 'views',
    'field'        => 'nothing',
    'relationship' => 'none',
    'group_type'   => 'group',
    'admin_label'  => '',
    'label'        => '',
    'exclude'      => FALSE,
    'alter'        => [
      'alter_text'     => TRUE,
      'text'           => '<a href="/user/[order_id]/orders/[order_number]" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="fas fa-eye me-1"></i>View</a>',
      'make_link'      => FALSE,
      'path'           => '',
      'absolute'       => FALSE,
      'external'       => FALSE,
      'replace_spaces' => FALSE,
      'path_case'      => 'none',
      'trim_whitespace'=> FALSE,
      'alt'            => '',
      'rel'            => '',
      'link_class'     => '',
      'prefix'         => '',
      'suffix'         => '',
      'target'         => '',
      'nl2br'          => FALSE,
      'max_length'     => 0,
      'word_boundary'  => TRUE,
      'ellipsis'       => TRUE,
      'more_link'      => FALSE,
      'more_link_text' => '',
      'more_link_path' => '',
      'strip_tags'     => FALSE,
      'trim'           => FALSE,
      'preserve_tags'  => '',
      'html'           => FALSE,
    ],
    'element_type'            => '',
    'element_class'           => '',
    'element_label_type'      => '',
    'element_label_class'     => '',
    'element_label_colon'     => FALSE,
    'element_wrapper_type'    => '',
    'element_wrapper_class'   => '',
    'element_default_classes' => TRUE,
    'empty'           => '',
    'hide_empty'      => FALSE,
    'empty_zero'      => FALSE,
    'hide_alter_empty'=> TRUE,
    'plugin_id'       => 'custom',
  ];
  echo "Added 'nothing' custom text field to default display.\n";
}

// 3. Make order_id hidden (it's just a token source) and confirm it exists
if (isset($displays['default']['display_options']['fields']['order_id'])) {
  $displays['default']['display_options']['fields']['order_id']['exclude'] = TRUE;
  echo "Marked order_id as excluded (hidden, token only).\n";
}

$view->set('display', $displays);
$view->save();
echo "View saved.\n";

// Verify
$saved = \Drupal::entityTypeManager()->getStorage('view')->load('commerce_user_orders');
$d2 = $saved->get('display');
foreach ($d2 as $did => $d) {
  $fields = $d['display_options']['fields'] ?? [];
  echo $did . ' fields: ' . implode(', ', array_keys($fields)) . "\n";
}
