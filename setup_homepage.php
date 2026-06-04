<?php
/**
 * Setup script: Homepage node + featured products block + front page config.
 *
 * 1. Creates a Basic Page node "Home" (no body — blocks do the work)
 * 2. Sets /node/{id} as the Drupal front page
 * 3. Adds a block display to the product_catalog View for featured products
 * 4. Places the featured products block in the content region (front page only)
 * 5. Places the main content block (node system block) so the node renders
 */

use Drupal\node\Entity\Node;
use Drupal\block\Entity\Block;

// ── 1. Create the Home page node ─────────────────────────────────────────────
$existing = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['title' => 'Home', 'type' => 'page']);

if ($existing) {
  $home_node = reset($existing);
  echo "Home node already exists: NID " . $home_node->id() . "\n";
} else {
  $home_node = Node::create([
    'type'      => 'page',
    'title'     => 'Home',
    'status'    => 1,
    'uid'       => 1,
    'body'      => [
      'value'  => '',
      'format' => 'full_html',
    ],
  ]);
  $home_node->save();
  echo "Created Home page node: NID " . $home_node->id() . "\n";
}

$nid = $home_node->id();

// ── 2. Set the node as the site front page ────────────────────────────────────
\Drupal::configFactory()
  ->getEditable('system.site')
  ->set('page.front', '/node/' . $nid)
  ->save();
echo "Front page set to: /node/$nid\n";

// ── 3. Add a block display to product_catalog View ───────────────────────────
$view = \Drupal::entityTypeManager()->getStorage('view')->load('product_catalog');
$displays = $view->get('display');

if (!isset($displays['block_featured'])) {
  // Copy defaults from the default display
  $block_display = [
    'id'              => 'block_featured',
    'display_title'   => 'Featured Products (Block)',
    'display_plugin'  => 'block',
    'position'        => 2,
    'display_options' => [
      'display_extenders' => [],
      'title'       => 'Featured Products',
      'block_description' => 'Featured Products',
      'pager'       => [
        'type'    => 'some',
        'options' => ['items_per_page' => 6, 'offset' => 0],
      ],
      'defaults' => [
        'title'  => FALSE,
        'pager'  => FALSE,
      ],
    ],
  ];
  $displays['block_featured'] = $block_display;
  $view->set('display', $displays);
  $view->save();
  echo "Added block display 'block_featured' to product_catalog view\n";
} else {
  echo "Block display 'block_featured' already exists\n";
}

// ── 4. Place the featured products block in content region ───────────────────
$featured_block_id = 'commerce_theme_featured_products';
$existing_block = Block::load($featured_block_id);
if ($existing_block) {
  $existing_block->delete();
}

Block::create([
  'id'     => $featured_block_id,
  'theme'  => 'commerce_theme',
  'region' => 'content',
  'plugin' => 'views_block:product_catalog-block_featured',
  'weight' => 10,
  'settings' => [
    'id'            => 'views_block:product_catalog-block_featured',
    'label'         => 'Featured Products',
    'label_display' => '0',
    'status'        => TRUE,
    'items_per_page' => 'none',
  ],
  'visibility' => [
    'request_path' => [
      'id'              => 'request_path',
      'pages'           => '<front>',
      'negate'          => FALSE,
      'context_mapping' => [],
    ],
  ],
])->save();
echo "Placed featured products block in content region (front page only)\n";

// ── 5. Ensure the system main content block is placed ────────────────────────
// This renders the node body (if any) in the content region.
// Usually already exists as 'commerce_theme_content'.
$main_block = Block::load('commerce_theme_content');
if (!$main_block) {
  Block::create([
    'id'     => 'commerce_theme_content',
    'theme'  => 'commerce_theme',
    'region' => 'content',
    'plugin' => 'system_main_block',
    'weight' => 0,
    'settings' => ['id' => 'system_main_block', 'label' => '', 'label_display' => '0'],
    'visibility' => [],
  ])->save();
  echo "Placed system main content block\n";
} else {
  echo "System main content block already placed\n";
}

echo "\n✓ Done!\n";
echo "  Front page  : /node/$nid  (Home)\n";
echo "  Hero banner : hero region (already placed)\n";
echo "  Featured Px : content region (front page only)\n";
echo "  Visit       : https://new-drupal.ddev.site\n";
