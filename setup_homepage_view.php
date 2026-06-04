<?php

/**
 * @file
 * Imports the homepage_category_products view from the theme config/install.
 *
 * Run with:
 *   drush php:script setup_homepage_view.php
 */

use Drupal\Core\Serialization\Yaml;

$config_file = __DIR__ . '/web/themes/custom/commerce_theme/config/install/views.view.homepage_category_products.yml';

if (!file_exists($config_file)) {
  echo "ERROR: Config file not found at {$config_file}\n";
  exit(1);
}

$data = Yaml::decode(file_get_contents($config_file));

\Drupal::service('config.factory')
  ->getEditable('views.view.homepage_category_products')
  ->setData($data)
  ->save();

// Rebuild caches so Drupal picks up the new view.
drupal_flush_all_caches();

echo "Done: views.view.homepage_category_products imported and caches cleared.\n";
