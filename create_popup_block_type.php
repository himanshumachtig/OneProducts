<?php

use Drupal\block_content\Entity\BlockContentType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

// 1. Create the Block Content Type.
if (!BlockContentType::load('popup')) {
  BlockContentType::create([
    'id'          => 'popup',
    'label'       => 'Popup',
    'description' => 'Popup block with title, image, and link.',
    'revision'    => FALSE,
  ])->save();
  echo "✔ Block type 'Popup' created.\n";
} else {
  echo "  Block type 'Popup' already exists, skipping.\n";
}

// 2. Image field storage.
if (!FieldStorageConfig::loadByName('block_content', 'field_popup_image')) {
  FieldStorageConfig::create([
    'field_name'  => 'field_popup_image',
    'entity_type' => 'block_content',
    'type'        => 'image',
    'cardinality' => 1,
    'settings'    => [
      'uri_scheme'    => 'public',
      'default_image' => ['uuid' => NULL, 'alt' => '', 'title' => '', 'width' => NULL, 'height' => NULL],
    ],
  ])->save();
  echo "✔ Image field storage created.\n";
} else {
  echo "  Image field storage already exists, skipping.\n";
}

// 3. Image field instance on popup bundle.
if (!FieldConfig::loadByName('block_content', 'popup', 'field_popup_image')) {
  FieldConfig::create([
    'field_name'  => 'field_popup_image',
    'entity_type' => 'block_content',
    'bundle'      => 'popup',
    'label'       => 'Popup Image',
    'required'    => FALSE,
    'settings'    => [
      'file_directory'    => 'popup-images',
      'file_extensions'   => 'png jpg jpeg webp',
      'alt_field'         => TRUE,
      'alt_field_required'=> FALSE,
      'title_field'       => FALSE,
      'max_filesize'      => '',
      'max_resolution'    => '',
      'min_resolution'    => '',
      'default_image'     => ['uuid' => NULL, 'alt' => '', 'title' => '', 'width' => NULL, 'height' => NULL],
    ],
  ])->save();
  echo "✔ Image field added to Popup.\n";
} else {
  echo "  Image field already exists on Popup, skipping.\n";
}

// 4. Link field storage.
if (!FieldStorageConfig::loadByName('block_content', 'field_popup_link')) {
  FieldStorageConfig::create([
    'field_name'  => 'field_popup_link',
    'entity_type' => 'block_content',
    'type'        => 'link',
    'cardinality' => 1,
  ])->save();
  echo "✔ Link field storage created.\n";
} else {
  echo "  Link field storage already exists, skipping.\n";
}

// 5. Link field instance on popup bundle.
if (!FieldConfig::loadByName('block_content', 'popup', 'field_popup_link')) {
  FieldConfig::create([
    'field_name'  => 'field_popup_link',
    'entity_type' => 'block_content',
    'bundle'      => 'popup',
    'label'       => 'Popup Link',
    'required'    => FALSE,
    'settings'    => [
      'title'     => 1,   // allow link text
      'link_type' => 17,  // internal + external
    ],
  ])->save();
  echo "✔ Link field added to Popup.\n";
} else {
  echo "  Link field already exists on Popup, skipping.\n";
}

// 6. Form display — arrange fields in the edit form.
$form_display = EntityFormDisplay::load('block_content.popup.default');
if (!$form_display) {
  $form_display = EntityFormDisplay::create([
    'targetEntityType' => 'block_content',
    'bundle'           => 'popup',
    'mode'             => 'default',
    'status'           => TRUE,
  ]);
}
$form_display
  ->setComponent('info', [
    'type'     => 'string_textfield',
    'weight'   => -5,
    'settings' => ['size' => 60, 'placeholder' => 'Popup headline…'],
  ])
  ->setComponent('field_popup_image', [
    'type'     => 'image_image',
    'weight'   => 1,
    'settings' => ['progress_indicator' => 'throbber', 'preview_image_style' => 'thumbnail'],
  ])
  ->setComponent('field_popup_link', [
    'type'     => 'link_default',
    'weight'   => 2,
    'settings' => ['placeholder_url' => 'https://…', 'placeholder_title' => 'Button label'],
  ])
  ->save();
echo "✔ Form display configured.\n";

// 7. View display — hidden labels, sensible image style.
$view_display = EntityViewDisplay::load('block_content.popup.default');
if (!$view_display) {
  $view_display = EntityViewDisplay::create([
    'targetEntityType' => 'block_content',
    'bundle'           => 'popup',
    'mode'             => 'default',
    'status'           => TRUE,
  ]);
}
$view_display
  ->setComponent('field_popup_image', [
    'type'     => 'image',
    'weight'   => 1,
    'label'    => 'hidden',
    'settings' => ['image_style' => 'large', 'image_link' => ''],
  ])
  ->setComponent('field_popup_link', [
    'type'     => 'link',
    'weight'   => 2,
    'label'    => 'hidden',
    'settings' => ['trim_length' => 80, 'url_only' => FALSE, 'url_plain' => FALSE, 'target' => ''],
  ])
  ->save();
echo "✔ View display configured.\n";

echo "\n✅ Done. Go to Structure → Block layout → Custom block library to add a Popup block.\n";
