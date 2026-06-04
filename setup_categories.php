<?php
/**
 * Setup: Category-based product display system.
 *
 * 1. Add page_category display to product_catalog view (path: /category/%)
 * 2. Create path aliases for each category with products
 * 3. Add category items to the main menu under a "Shop" parent
 */

use Drupal\path_alias\Entity\PathAlias;
use Drupal\menu_link_content\Entity\MenuLinkContent;

// ── 1. Add page_category display to product_catalog view ──────────────────────
$view = \Drupal::entityTypeManager()->getStorage('view')->load('product_catalog');
$displays = $view->get('display');

if (!isset($displays['page_category'])) {
  $displays['page_category'] = [
    'id'             => 'page_category',
    'display_title'  => 'Category Page',
    'display_plugin' => 'page',
    'position'       => 3,
    'display_options' => [
      'display_extenders' => [],
      'path'   => 'category/%',
      'title'  => '',
      'defaults' => [
        'arguments' => FALSE,
        'title'     => FALSE,
        'filters'   => FALSE,
      ],
      'arguments' => [
        'field_category_target_id' => [
          'id'            => 'field_category_target_id',
          'table'         => 'commerce_product__field_category',
          'field'         => 'field_category_target_id',
          'relationship'  => 'none',
          'group_type'    => 'group',
          'admin_label'   => 'Category (taxonomy term ID)',
          'entity_type'   => 'commerce_product',
          'entity_field'  => 'field_category',
          'plugin_id'     => 'numeric',
          'default_action' => 'not found',
          'exception'     => ['value' => 'all', 'title_enable' => FALSE, 'title' => 'All'],
          'title_enable'  => FALSE,
          'title'         => '',
          'default_argument_type'    => 'fixed',
          'default_argument_options' => ['argument' => ''],
          'default_argument_skip_url' => FALSE,
          'summary_options' => [
            'base_path'     => '',
            'count'         => TRUE,
            'items_per_page' => 25,
            'override'      => FALSE,
          ],
          'summary' => [
            'sort_order'       => 'asc',
            'number_of_records' => 0,
            'format'           => 'default_summary',
          ],
          'specify_validation' => FALSE,
          'validate'           => ['type' => 'none', 'fail' => 'not found'],
          'validate_options'   => [],
          'break_phrase'       => FALSE,
          'not'                => FALSE,
          'error_message'      => TRUE,
          'limit'              => FALSE,
        ],
      ],
      // No exposed filter on category page (it's set by URL argument)
      'filters' => [
        'status' => [
          'id'        => 'status',
          'table'     => 'commerce_product_field_data',
          'field'     => 'status',
          'plugin_id' => 'boolean',
          'value'     => '1',
        ],
      ],
    ],
  ];
  $view->set('display', $displays);
  $view->save();
  echo "Added page_category display to product_catalog view\n";
  echo "  URL pattern: /category/{term_id}\n";
} else {
  echo "page_category display already exists\n";
}

// ── 2. Create path aliases and menu items for each category with products ────
$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');

// Load only terms that have at least one product assigned
$all_terms = $term_storage->loadByProperties(['vid' => 'product_category']);
$active_terms = [];
foreach ($all_terms as $term) {
  $count = $product_storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('status', 1)
    ->condition('field_category', $term->id())
    ->count()
    ->execute();
  if ($count > 0) {
    $active_terms[] = $term;
  }
}

echo "\nActive categories (have products):\n";

// Load alias manager for cleanup
$alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');

// Find or create "Shop" parent menu item
$menu_storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
$existing_shop = $menu_storage->loadByProperties([
  'title'     => 'Shop',
  'menu_name' => 'main',
]);
if ($existing_shop) {
  $shop_parent = reset($existing_shop);
  echo "\nUsing existing 'Shop' menu item as parent\n";
} else {
  $shop_parent = MenuLinkContent::create([
    'title'     => 'Shop',
    'link'      => ['uri' => 'internal:/products'],
    'menu_name' => 'main',
    'expanded'  => TRUE,
    'weight'    => 10,
  ]);
  $shop_parent->save();
  echo "\nCreated 'Shop' parent menu item\n";
}

$parent_plugin_id = 'menu_link_content:' . $shop_parent->uuid();

foreach ($active_terms as $term) {
  $tid   = $term->id();
  $label = $term->label();
  // Build a URL-safe slug from the term label
  $slug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $label));
  $slug  = trim($slug, '-');

  $internal_path = '/category/' . $tid;
  $alias_path    = '/category/' . $slug;

  echo "  TID $tid: $label → $alias_path\n";

  // Delete any existing alias for this internal path
  $existing_aliases = $alias_storage->loadByProperties(['path' => $internal_path]);
  foreach ($existing_aliases as $existing_alias) {
    $existing_alias->delete();
  }

  // Create path alias
  PathAlias::create([
    'path'  => $internal_path,
    'alias' => $alias_path,
  ])->save();

  // Remove any existing category menu child item for this term
  $existing_links = $menu_storage->loadByProperties([
    'title'     => $label,
    'menu_name' => 'main',
  ]);
  foreach ($existing_links as $link) {
    $link->delete();
  }

  // Create child menu item under Shop
  MenuLinkContent::create([
    'title'     => $label,
    'link'      => ['uri' => 'internal:' . $alias_path],
    'menu_name' => 'main',
    'parent'    => $parent_plugin_id,
    'expanded'  => FALSE,
    'weight'    => $tid,
  ])->save();
}

echo "\n";

// ── 3. Rebuild routing and caches ─────────────────────────────────────────────
\Drupal::service('router.builder')->rebuild();

echo "✓ Done!\n";
echo "  Category pages:  /category/{slug}  (e.g. /category/laptops)\n";
echo "  Main menu:       Shop dropdown with category children\n";
echo "  Run 'ddev drush cr' to clear caches.\n";
