<?php
/**
 * Set up color variations, gallery images, and size options for all clothing products.
 *
 * For each clothing product (IDs 31-130, plus 30):
 *   - Generates 4 color-variant images using GD
 *   - Creates 4 colors × 6 sizes = 24 variations per product
 *   - Sets 2 gallery images on the product (field_product_gallery)
 *   - Sets 1 color-specific image per variation (field_variation_images)
 *
 * Run: ddev drush php-script setup_product_variations.php
 */

// ── Rounded-rectangle polyfill ────────────────────────────────────────────────
if (!function_exists('imagefilledroundedrectangle')) {
  function imagefilledroundedrectangle($img, $x1, $y1, $x2, $y2, $r, $color) {
    imagefilledrectangle($img, $x1+$r, $y1, $x2-$r, $y2, $color);
    imagefilledrectangle($img, $x1, $y1+$r, $x2, $y2-$r, $color);
    imagefilledellipse($img,$x1+$r,$y1+$r,$r*2,$r*2,$color);
    imagefilledellipse($img,$x2-$r,$y1+$r,$r*2,$r*2,$color);
    imagefilledellipse($img,$x1+$r,$y2-$r,$r*2,$r*2,$color);
    imagefilledellipse($img,$x2-$r,$y2-$r,$r*2,$r*2,$color);
  }
}

// ── Core GD renderer (identical to regen_product_images.php) ─────────────────
function render_clothing_image(string $type, array $rgb, string $label, string $filepath): void {
  [$r,$g,$b] = $rgb;
  $img   = imagecreatetruecolor(400, 400);
  $bgc   = imagecolorallocate($img, 245, 245, 248);
  $col   = imagecolorallocate($img, $r, $g, $b);
  $dark  = imagecolorallocate($img, max(0,$r-70), max(0,$g-70), max(0,$b-70));
  $vdark = imagecolorallocate($img, max(0,$r-120),max(0,$g-120),max(0,$b-120));
  $light = imagecolorallocate($img, min(255,$r+70), min(255,$g+70), min(255,$b+70));
  $white = imagecolorallocate($img, 255,255,255);
  $grey  = imagecolorallocate($img, 170,170,170);
  $outline = ($r+$g+$b > 580) ? imagecolorallocate($img,130,130,130) : $dark;

  imagefilledrectangle($img, 0, 0, 400, 400, $bgc);
  // Footer label bar
  imagefilledrectangle($img, 0, 360, 400, 400, $dark);
  $words = explode(' ', $label);
  $lines = []; $ln = '';
  foreach ($words as $w) {
    if ($ln !== '' && strlen("$ln $w") > 26) { $lines[] = $ln; $ln = $w; }
    else { $ln = $ln === '' ? $w : "$ln $w"; }
  }
  $lines[] = $ln;
  $ty = 362 + (int)((38 - count($lines)*16)/2);
  foreach ($lines as $l) {
    $tw = (int)(imagefontwidth(3)*strlen($l));
    imagestring($img, 3, (400-$tw)/2, $ty, $l, $white);
    $ty += 16;
  }

  switch ($type) {
    case 'tshirt':
      imagefilledpolygon($img,[148,95,122,65,58,48,42,155,115,172,115,340,285,340,285,172,358,155,342,48,278,65,252,95],$col);
      imagefilledellipse($img,200,88,110,58,$bgc);
      imagearc($img,200,78,110,58,5,175,$outline);
      imageline($img,115,172,115,340,$outline);
      imageline($img,285,172,285,340,$outline);
      imageline($img,115,340,285,340,$outline);
      imageline($img,42,155,115,172,$outline);
      imageline($img,285,172,358,155,$outline);
      break;
    case 'shirt':
      imagefilledpolygon($img,[148,92,118,62,55,240,78,260,118,175,118,345,282,345,282,175,322,260,345,240,282,62,252,92],$col);
      imagefilledpolygon($img,[148,92,200,148,170,92],$light);
      imagefilledpolygon($img,[252,92,200,148,230,92],$light);
      imageline($img,148,92,200,148,$outline);
      imageline($img,200,148,252,92,$outline);
      imageline($img,200,148,200,345,$outline);
      foreach([175,210,245,280,315] as $by) imagefilledellipse($img,200,$by,9,9,$outline);
      imageline($img,118,175,118,345,$outline);
      imageline($img,282,175,282,345,$outline);
      imageline($img,118,345,282,345,$outline);
      imageline($img,55,240,78,260,$outline);
      imageline($img,322,260,345,240,$outline);
      break;
    case 'jeans':
      imagefilledpolygon($img,[108,55,292,55,292,350,222,350,200,182,178,350,108,350],$col);
      imagefilledrectangle($img,108,55,292,100,$dark);
      imageline($img,108,55,292,55,$vdark);
      foreach([140,200,260] as $bx) {
        imagefilledrectangle($img,$bx-7,52,$bx+7,104,$light);
        imagefilledrectangle($img,$bx-5,55,$bx+5,100,$dark);
      }
      imagefilledellipse($img,200,73,14,14,$light);
      imagefilledellipse($img,200,73,8,8,$vdark);
      imageline($img,200,87,200,182,$vdark);
      imagearc($img,132,107,90,55,0,95,$light);
      imagearc($img,268,107,90,55,85,180,$light);
      imageline($img,108,100,108,350,$vdark);
      imageline($img,292,100,292,350,$vdark);
      imageline($img,200,182,178,350,$vdark);
      imageline($img,200,182,222,350,$vdark);
      imageline($img,108,350,178,350,$vdark);
      imageline($img,222,350,292,350,$vdark);
      break;
    case 'shorts':
      imagefilledpolygon($img,[108,55,292,55,292,240,222,240,200,165,178,240,108,240],$col);
      imagefilledrectangle($img,108,55,292,100,$dark);
      imageline($img,108,55,292,55,$vdark);
      foreach([140,200,260] as $bx) {
        imagefilledrectangle($img,$bx-7,52,$bx+7,104,$light);
        imagefilledrectangle($img,$bx-5,55,$bx+5,100,$dark);
      }
      imagefilledellipse($img,200,73,14,14,$light);
      imagefilledellipse($img,200,73,8,8,$vdark);
      imageline($img,200,87,200,165,$vdark);
      imagearc($img,132,107,90,55,0,95,$light);
      imagearc($img,268,107,90,55,85,180,$light);
      imageline($img,108,100,108,240,$vdark);
      imageline($img,292,100,292,240,$vdark);
      imageline($img,200,165,178,240,$vdark);
      imageline($img,200,165,222,240,$vdark);
      imageline($img,108,240,178,240,$vdark);
      imageline($img,222,240,292,240,$vdark);
      break;
    case 'jacket':
      imagefilledpolygon($img,[145,88,115,60,50,245,75,265,115,175,115,345,285,345,285,175,325,265,350,245,285,60,255,88],$col);
      imagefilledpolygon($img,[145,88,200,165,160,88],$light);
      imageline($img,145,88,200,165,$outline);
      imagefilledpolygon($img,[255,88,200,165,240,88],$light);
      imageline($img,200,165,255,88,$outline);
      imagefilledpolygon($img,[160,88,145,88,148,68,200,75,252,68,255,88,240,88,200,105],$dark);
      imageline($img,200,165,200,345,$dark);
      imagefilledroundedrectangle($img,122,262,175,285,5,$dark);
      imageline($img,122,262,175,262,$light);
      imagefilledroundedrectangle($img,225,262,278,285,5,$dark);
      imageline($img,225,262,278,262,$light);
      imageline($img,115,175,115,345,$outline);
      imageline($img,285,175,285,345,$outline);
      imageline($img,115,345,285,345,$outline);
      break;
    case 'hoodie':
      imagefilledpolygon($img,[148,95,122,65,58,48,42,155,115,172,115,340,285,340,285,172,358,155,342,48,278,65,252,95],$col);
      imagefilledarc($img,200,95,175,155,185,355,$col,IMG_ARC_PIE);
      imagefilledellipse($img,200,95,105,105,$bgc);
      imagearc($img,200,95,175,155,185,355,$dark);
      imagearc($img,200,95,105,105,185,355,$dark);
      imageline($img,168,98,148,130,$dark);
      imageline($img,232,98,252,130,$dark);
      imagefilledellipse($img,148,133,8,8,$dark);
      imagefilledellipse($img,252,133,8,8,$dark);
      imagefilledroundedrectangle($img,148,268,252,305,8,$dark);
      imageline($img,148,268,252,268,$light);
      imageline($img,115,172,115,340,$outline);
      imageline($img,285,172,285,340,$outline);
      imageline($img,115,340,285,340,$outline);
      break;
    case 'shoes':
      // Sole
      imagefilledpolygon($img,[80,280,80,310,320,310,320,280],$vdark);
      // Upper body
      imagefilledpolygon($img,[80,280,110,180,180,160,290,170,320,220,320,280],$col);
      // Toe box
      imagefilledellipse($img,120,265,100,50,$col);
      // Tongue
      imagefilledpolygon($img,[175,170,225,170,215,240,185,240],$light);
      // Laces
      foreach([185,200,215,230] as $ly) {
        imageline($img,175,$ly,225,$ly,$outline);
      }
      // Heel counter
      imagefilledpolygon($img,[285,175,320,210,320,280,285,280],$dark);
      // Side stripe
      imageline($img,130,240,290,220,$light);
      imageline($img,130,250,290,230,$light);
      // Outline
      imageline($img,80,280,320,280,$vdark);
      break;
    default:
      // Generic rectangle placeholder
      imagefilledrectangle($img,80,60,320,340,$col);
      break;
  }

  imagepng($img, $filepath);
  imagedestroy($img);
  echo "  [img] " . basename($filepath) . "\n";
}

// ── Create or load a Drupal managed file entity ───────────────────────────────
function get_or_create_file(string $uri): \Drupal\file\Entity\File {
  $existing = \Drupal::entityTypeManager()->getStorage('file')
    ->loadByProperties(['uri' => $uri]);
  if ($existing) return reset($existing);

  $file = \Drupal\file\Entity\File::create([
    'uri'    => $uri,
    'uid'    => 1,
    'status' => 1,
  ]);
  $file->save();
  return $file;
}

// ── Get or create a color attribute value ─────────────────────────────────────
function get_or_create_color_value(string $label, string $hex): int {
  $storage = \Drupal::entityTypeManager()->getStorage('commerce_product_attribute_value');
  $existing = $storage->loadByProperties(['attribute' => 'color', 'name' => $label]);
  if ($existing) return (int) reset($existing)->id();

  $val = \Drupal\commerce_product\Entity\ProductAttributeValue::create([
    'attribute' => 'color',
    'name'      => $label,
  ]);
  $val->save();

  // Set the hex field if it exists
  if ($val->hasField('field_color_hex')) {
    $val->set('field_color_hex', $hex);
    $val->save();
  }
  return (int) $val->id();
}

// ── Load size attribute value ID by label ─────────────────────────────────────
function get_size_id(string $label): int {
  $vals = \Drupal::entityTypeManager()->getStorage('commerce_product_attribute_value')
    ->loadByProperties(['attribute' => 'size', 'name' => $label]);
  if (!$vals) throw new \RuntimeException("Size '$label' not found");
  return (int) reset($vals)->id();
}

// ═══════════════════════════════════════════════════════════════════════════════
// DATA DEFINITIONS
// ═══════════════════════════════════════════════════════════════════════════════

// All available colors with their hex and GD RGB
$ALL_COLORS = [
  'White'  => ['hex' => '#F0F0F0', 'rgb' => [220, 220, 220]],
  'Black'  => ['hex' => '#1C1C1C', 'rgb' => [28,  28,  28]],
  'Navy'   => ['hex' => '#1B2A4A', 'rgb' => [27,  42,  74]],
  'Red'    => ['hex' => '#C0392B', 'rgb' => [192, 57,  43]],
  'Green'  => ['hex' => '#27AE60', 'rgb' => [39,  174, 96]],
  'Blue'   => ['hex' => '#2980B9', 'rgb' => [41,  128, 185]],
  'Gray'   => ['hex' => '#808080', 'rgb' => [128, 128, 128]],
  'Purple' => ['hex' => '#8E44AD', 'rgb' => [142, 68,  173]],
  'Orange' => ['hex' => '#E67E22', 'rgb' => [230, 126, 34]],
  'Pink'   => ['hex' => '#E91E63', 'rgb' => [233, 30,  99]],
  'Olive'  => ['hex' => '#556B2F', 'rgb' => [85,  107, 47]],
  'Brown'  => ['hex' => '#8B5A2B', 'rgb' => [139, 90,  43]],
];

// Colors per garment type
$TYPE_COLORS = [
  'jeans'  => ['Navy', 'Black', 'Blue', 'Gray'],
  'tshirt' => ['White', 'Black', 'Red', 'Green'],
  'shirt'  => ['White', 'Blue', 'Gray', 'Pink'],
  'jacket' => ['Black', 'Navy', 'Olive', 'Brown'],
  'hoodie' => ['Black', 'Gray', 'Purple', 'Blue'],
  'shorts' => ['Navy', 'Black', 'Blue', 'Green'],
  'shoes'  => ['White', 'Black', 'Brown', 'Gray'],
];

$SIZES = ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

// Catalog: [product_id, image_type, label]
// image types match regen_product_images.php exactly
$catalog = [
  // Men's Jeans (IDs 31-40)
  [31, 'jeans',  "Men's Slim Fit Dark Wash Jeans"],
  [32, 'jeans',  "Men's Relaxed Fit Straight Jeans"],
  [33, 'jeans',  "Men's Skinny Stretch Jeans"],
  [34, 'jeans',  "Men's Classic Bootcut Jeans"],
  [35, 'jeans',  "Men's Distressed Ripped Jeans"],
  [36, 'jeans',  "Men's Tapered Light Wash Jeans"],
  [37, 'jeans',  "Men's Multi-Pocket Cargo Jeans"],
  [38, 'jeans',  "Men's Jogger-Style Denim Jeans"],
  [39, 'jeans',  "Men's Raw Selvedge Straight Jeans"],
  [40, 'jeans',  "Men's Wide Leg Denim Jeans"],
  // Men's T-Shirts (IDs 41-50)
  [41, 'tshirt', "Men's Classic Crew Neck T-Shirt"],
  [42, 'tshirt', "Men's Graphic Print T-Shirt"],
  [43, 'tshirt', "Men's V-Neck Slim T-Shirt"],
  [44, 'tshirt', "Men's Pocket Chest T-Shirt"],
  [45, 'tshirt', "Men's Long Sleeve T-Shirt"],
  [46, 'tshirt', "Men's Henley Button T-Shirt"],
  [47, 'tshirt', "Men's Breton Stripe T-Shirt"],
  [48, 'tshirt', "Men's Performance Dry-Fit T-Shirt"],
  [49, 'tshirt', "Men's Tie-Dye Oversized T-Shirt"],
  [50, 'tshirt', "Men's Longline Extended T-Shirt"],
  // Men's Shirts (IDs 51-60)
  [51, 'shirt',  "Men's Classic Oxford Button Shirt"],
  [52, 'shirt',  "Men's Linen Summer Shirt"],
  [53, 'shirt',  "Men's Flannel Check Shirt"],
  [54, 'shirt',  "Men's Slim Fit Formal Shirt"],
  [55, 'shirt',  "Men's Denim Western Shirt"],
  [56, 'shirt',  "Men's Hawaiian Camp Collar Shirt"],
  [57, 'shirt',  "Men's Chambray Work Shirt"],
  [58, 'shirt',  "Men's Dobby Texture Formal Shirt"],
  [59, 'shirt',  "Men's Mandarin Collar Linen Shirt"],
  [60, 'jacket', "Men's Twill Overshirt Jacket"],
  // Men's Jackets (IDs 61-70)
  [61, 'jacket', "Men's Genuine Leather Biker Jacket"],
  [62, 'jacket', "Men's Quilted Puffer Jacket"],
  [63, 'jacket', "Men's Lightweight Bomber Jacket"],
  [64, 'jacket', "Men's Wool-Cashmere Overcoat"],
  [65, 'jacket', "Men's Waxed Cotton Field Jacket"],
  [66, 'jacket', "Men's Lightweight Windbreaker"],
  [67, 'jacket', "Men's Softshell Fleece Jacket"],
  [68, 'jacket', "Men's Varsity College Jacket"],
  [69, 'jacket', "Men's Military Cargo Jacket"],
  [70, 'jacket', "Men's Denim Trucker Jacket"],
  // Men's Shoes (IDs 71-80)
  [71, 'shoes',  "Men's Clean White Leather Sneakers"],
  [72, 'shoes',  "Men's Suede Chelsea Boots"],
  [73, 'shoes',  "Men's Road Running Trainers"],
  [74, 'shoes',  "Men's Oxford Dress Shoes"],
  [75, 'shoes',  "Men's Canvas High-Top Sneakers"],
  [76, 'shoes',  "Men's Leather Penny Loafers"],
  [77, 'shoes',  "Men's Waterproof Hiking Boots"],
  [78, 'shoes',  "Men's Jute Canvas Espadrilles"],
  [79, 'shoes',  "Men's Wingtip Derby Brogues"],
  [80, 'shoes',  "Men's Chunky Platform Sneakers"],
  // Kids' T-Shirts (IDs 81-90)
  [81, 'tshirt', "Kids' Dinosaur Print T-Shirt"],
  [82, 'tshirt', "Kids' Superhero Graphic Tee"],
  [83, 'tshirt', "Kids' Rainbow Stripe T-Shirt"],
  [84, 'tshirt', "Kids' Space Explorer T-Shirt"],
  [85, 'tshirt', "Kids' Animal Print T-Shirt"],
  [86, 'tshirt', "Kids' Tie-Dye Effect T-Shirt"],
  [87, 'tshirt', "Kids' Sports Number T-Shirt"],
  [88, 'tshirt', "Kids' Plain Essential T-Shirt"],
  [89, 'tshirt', "Kids' Long Sleeve T-Shirt"],
  [90, 'tshirt', "Kids' Pocket T-Shirt"],
  // Kids' Jeans (IDs 91-100)
  [91,  'jeans', "Kids' Slim Fit Dark Wash Jeans"],
  [92,  'jeans', "Kids' Elasticated Waist Jeans"],
  [93,  'jeans', "Kids' Ripped Knee Jeans"],
  [94,  'jeans', "Kids' Skinny Stretch Jeans"],
  [95,  'jeans', "Kids' Jogger Denim Jeans"],
  [96,  'jeans', "Kids' Light Wash Straight Jeans"],
  [97,  'jeans', "Kids' Cargo Denim Jeans"],
  [98,  'jeans', "Kids' Pull-On Jeggings"],
  [99,  'jeans', "Kids' Dungaree Denim Overalls"],
  [100, 'jeans', "Kids' Wide Leg Barrel Jeans"],
  // Kids' Shorts (IDs 101-110)
  [101, 'shorts', "Kids' Jersey Sport Shorts"],
  [102, 'shorts', "Kids' Denim Cutoff Shorts"],
  [103, 'shorts', "Kids' Quick-Dry Swim Shorts"],
  [104, 'shorts', "Kids' Cargo Shorts"],
  [105, 'shorts', "Kids' Floral Print Shorts"],
  [106, 'shorts', "Kids' Chino Shorts"],
  [107, 'shorts', "Kids' Cycling Shorts"],
  [108, 'shorts', "Kids' Tie-Dye Jersey Shorts"],
  [109, 'shorts', "Kids' Linen Blend Shorts"],
  [110, 'shorts', "Kids' Roll-Hem Jersey Shorts"],
  // Kids' Hoodies (IDs 111-120)
  [111, 'hoodie', "Kids' Rainbow Zip-Up Hoodie"],
  [112, 'hoodie', "Kids' Camo Pullover Hoodie"],
  [113, 'hoodie', "Kids' Character Print Hoodie"],
  [114, 'hoodie', "Kids' Fleece-Lined Pullover Hoodie"],
  [115, 'hoodie', "Kids' Tie-Dye Pullover Hoodie"],
  [116, 'hoodie', "Kids' Galaxy Print Hoodie"],
  [117, 'hoodie', "Kids' Varsity Logo Hoodie"],
  [118, 'hoodie', "Kids' Windproof Outdoor Hoodie"],
  [119, 'hoodie', "Kids' Striped Pullover Hoodie"],
  [120, 'hoodie', "Kids' Sherpa Fleece Hoodie"],
  // Kids' Shoes (IDs 121-130)
  [121, 'shoes', "Kids' Canvas Lace-Up Sneakers"],
  [122, 'shoes', "Kids' Velcro Strap Trainers"],
  [123, 'shoes', "Kids' Wellington Rain Boots"],
  [124, 'shoes', "Kids' Leather School Shoes"],
  [125, 'shoes', "Kids' Sport Running Shoes"],
  [126, 'shoes', "Kids' Light-Up LED Trainers"],
  [127, 'shoes', "Kids' Chelsea Boots"],
  [128, 'shoes', "Kids' Slip-On Summer Sandals"],
  [129, 'shoes', "Kids' Winter Snow Boots"],
  [130, 'shoes', "Kids' Glitter High-Top Sneakers"],
];

// ═══════════════════════════════════════════════════════════════════════════════
// MAIN LOOP
// ═══════════════════════════════════════════════════════════════════════════════

$real_dir = \Drupal::service('file_system')->realpath('public://products/');
$product_storage   = \Drupal::entityTypeManager()->getStorage('commerce_product');
$variation_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
$currency_code     = 'INR';

// Pre-load all size IDs
$size_ids = [];
foreach ($SIZES as $size_label) {
  $size_ids[$size_label] = get_size_id($size_label);
}

$processed = 0;
$skipped   = 0;

foreach ($catalog as [$pid, $type, $label]) {
  $product = $product_storage->load($pid);
  if (!$product) { echo "  [SKIP] product $pid not found\n"; $skipped++; continue; }

  echo "\n[{$pid}] {$label} ({$type})\n";

  // ── Capture price from existing first variation ─────────────────────────────
  $existing_variations = $product->getVariations();
  $first_var = !empty($existing_variations) ? reset($existing_variations) : NULL;
  $price_num  = $first_var ? (float) $first_var->getPrice()->getNumber() : 999.00;
  $lp_num     = NULL;
  if ($first_var) {
    $lp_field = $first_var->get('list_price');
    if (!$lp_field->isEmpty()) $lp_num = (float) $lp_field->first()->toPrice()->getNumber();
  }
  $sku_base = $first_var ? preg_replace('/-[A-Z]{1,5}-[A-Z]+$/', '', $first_var->getSku()) : 'SKU-' . $pid;

  // ── Delete all existing variations so we can create fresh ones ──────────────
  foreach ($existing_variations as $var) {
    $product->get('variations')->filter(fn($item) => $item->target_id != $var->id());
    $var->delete();
  }
  $product->set('variations', []);

  // ── Determine colors for this product type ──────────────────────────────────
  $color_names = $TYPE_COLORS[$type] ?? ['Black', 'White', 'Gray', 'Blue'];

  // ── Generate color images and build variation/file data ────────────────────
  $color_files   = [];   // color_name → File entity
  $new_variations = [];  // all created variation entities

  foreach ($color_names as $color_name) {
    $color_info = $ALL_COLORS[$color_name];
    $fname      = "product-{$pid}-" . strtolower($color_name) . ".png";
    $uri        = "public://products/{$fname}";
    $fpath      = "{$real_dir}/{$fname}";

    // Generate the GD image
    render_clothing_image($type, $color_info['rgb'], $label . " ({$color_name})", $fpath);

    // Register as Drupal managed file
    $file = get_or_create_file($uri);
    $color_files[$color_name] = $file;

    // Get or create the color attribute value
    $color_attr_id = get_or_create_color_value($color_name, $color_info['hex']);

    // Create one variation per size for this color
    foreach ($SIZES as $size_label) {
      $sku_suffix = strtoupper(substr($color_name, 0, 1)) . '-' . $size_label;
      $sku = $sku_base . '-' . $sku_suffix;

      $variation = \Drupal\commerce_product\Entity\ProductVariation::create([
        'type'           => 'default',
        'sku'            => $sku,
        'price'          => new \Drupal\commerce_price\Price((string)$price_num, $currency_code),
        'status'         => 1,
        'attribute_color'=> $color_attr_id,
        'attribute_size' => $size_ids[$size_label],
        'field_variation_images' => [
          ['target_id' => $file->id(), 'alt' => "{$label} in {$color_name}"],
        ],
      ]);

      if ($lp_num) {
        $variation->set('list_price', new \Drupal\commerce_price\Price((string)$lp_num, $currency_code));
      }

      $variation->save();
      $new_variations[] = $variation;
      echo "    + {$color_name} / {$size_label} [vid:{$variation->id()}]\n";
    }
  }

  // ── Set variations on the product ──────────────────────────────────────────
  $product->set('variations', array_map(fn($v) => ['target_id' => $v->id()], $new_variations));

  // ── Set field_product_gallery (2 extra colour images) ─────────────────────
  $gallery_items = [];
  $gallery_count = 0;
  foreach ($color_files as $cname => $cfile) {
    if ($gallery_count >= 2) break;
    $gallery_items[] = ['target_id' => $cfile->id(), 'alt' => "{$label} – {$cname}"];
    $gallery_count++;
  }
  $product->set('field_product_gallery', $gallery_items);

  $product->save();
  echo "  ✓ Saved with " . count($new_variations) . " variations + {$gallery_count} gallery images\n";
  $processed++;
}

// ── Flush image style derivatives ─────────────────────────────────────────────
echo "\nFlushing image style derivatives...\n";
foreach (\Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple() as $style) {
  $style->flush();
}

// ── Rebuild entity caches ──────────────────────────────────────────────────────
\Drupal::entityTypeManager()->clearCachedDefinitions();
\Drupal::service('router.builder')->rebuild();

echo "\n✓ Done. Processed: $processed products, Skipped: $skipped.\n";
echo "  Run: ddev drush cr\n\n";
