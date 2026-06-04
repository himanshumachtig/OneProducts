<?php
/**
 * Adds a "View Details" custom text field to the commerce_user_orders view.
 */

$view = \Drupal::entityTypeManager()->getStorage('view')->load('commerce_user_orders');
if (!$view) {
  echo "ERROR: View 'commerce_user_orders' not found.\n";
  return;
}

$executable = $view->getExecutable();
$executable->initDisplay();

// Work directly on the stored config.
$display = &$view->getDisplay('order_page');
if (!$display) {
  echo "ERROR: Display 'order_page' not found.\n";
  return;
}

$fields = $display['display_options']['fields'] ?? [];

// Don't add twice.
if (isset($fields['nothing'])) {
  echo "Field 'nothing' already exists — skipping.\n";
} else {
  $display['display_options']['fields']['nothing'] = [
    'id'             => 'nothing',
    'table'          => 'views',
    'field'          => 'nothing',
    'relationship'   => 'none',
    'group_type'     => 'group',
    'admin_label'    => '',
    'label'          => '',
    'exclude'        => FALSE,
    'alter'          => [
      'text'          => '<a href="/user/{{ uid }}/orders/{{ order_id }}" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="fas fa-eye me-1"></i>View</a>',
      'make_link'     => FALSE,
      'path'          => '',
      'absolute'      => FALSE,
      'external'      => FALSE,
      'replace_spaces'=> FALSE,
      'path_case'     => 'none',
      'trim_whitespace'=> FALSE,
      'alt'           => '',
      'rel'           => '',
      'link_class'    => '',
      'prefix'        => '',
      'suffix'        => '',
      'target'        => '',
      'nl2br'         => FALSE,
      'max_length'    => 0,
      'word_boundary' => TRUE,
      'ellipsis'      => TRUE,
      'more_link'     => FALSE,
      'more_link_text'=> '',
      'more_link_path'=> '',
      'strip_tags'    => FALSE,
      'trim'          => FALSE,
      'preserve_tags' => '',
      'html'          => FALSE,
    ],
    'element_type'       => '',
    'element_class'      => '',
    'element_label_type' => '',
    'element_label_class'=> '',
    'element_label_colon'=> FALSE,
    'element_wrapper_type'  => '',
    'element_wrapper_class' => '',
    'element_default_classes'=> TRUE,
    'empty'          => '',
    'hide_empty'     => FALSE,
    'empty_zero'     => FALSE,
    'hide_alter_empty'=> TRUE,
    // The actual custom text uses tokens. We use the rewrite text field.
    'plugin_id'      => 'custom',
  ];
  echo "Added 'nothing' (custom text) field.\n";
}

// We also need to confirm uid and order_id tokens are available.
// Add uid field if missing (hidden).
if (!isset($fields['uid'])) {
  $display['display_options']['fields']['uid'] = [
    'id'           => 'uid',
    'table'        => 'commerce_order',
    'field'        => 'uid',
    'relationship' => 'none',
    'group_type'   => 'group',
    'admin_label'  => '',
    'label'        => 'Customer',
    'exclude'      => TRUE,
    'alter'        => ['alter_text' => FALSE, 'make_link' => FALSE, 'absolute' => FALSE, 'word_boundary' => TRUE, 'ellipsis' => TRUE, 'strip_tags' => FALSE, 'trim' => FALSE, 'nl2br' => FALSE, 'max_length' => 0, 'link_class' => '', 'prefix' => '', 'suffix' => '', 'target' => '', 'trim_whitespace' => FALSE, 'more_link' => FALSE, 'more_link_text' => '', 'more_link_path' => '', 'path' => '', 'replace_spaces' => FALSE, 'path_case' => 'none', 'external' => FALSE, 'rel' => '', 'text' => '', 'alt' => '', 'html' => FALSE, 'preserve_tags' => '', 'empty_zero' => FALSE, 'hide_alter_empty' => TRUE],
    'element_type' => '',
    'element_class' => '',
    'element_label_type' => '',
    'element_label_class' => '',
    'element_label_colon' => TRUE,
    'element_wrapper_type' => '',
    'element_wrapper_class' => '',
    'element_default_classes' => TRUE,
    'empty' => '',
    'hide_empty' => FALSE,
    'empty_zero' => FALSE,
    'hide_alter_empty' => TRUE,
    'click_sort_column' => 'target_id',
    'type' => 'entity_reference_label',
    'settings' => ['link' => FALSE],
    'group_column' => 'target_id',
    'group_columns' => [],
    'group_rows' => TRUE,
    'delta_limit' => 0,
    'delta_offset' => 0,
    'delta_reversed' => FALSE,
    'delta_first_last' => FALSE,
    'multi_type' => 'separator',
    'separator' => ', ',
    'field_api_classes' => FALSE,
    'plugin_id' => 'field',
  ];
  echo "Added hidden uid field.\n";
}

$view->save();
echo "View saved.\n";

// Verify
$saved = \Drupal::entityTypeManager()->getStorage('view')->load('commerce_user_orders');
$d = $saved->getDisplay('order_page');
echo "Fields in order_page: " . implode(', ', array_keys($d['display_options']['fields'] ?? [])) . "\n";
