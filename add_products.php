<?php
/**
 * Add sample Commerce products with GD-generated placeholder images.
 *
 * Products added:
 *  Phones  (TID 6) — 10 new products
 *  Tablets (TID 5) — 4 new products
 *  Laptops (TID 7) — 4 new products
 *  Audio   (TID 8) — 4 new products
 */

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\file\Entity\File;

// ── Helper: generate a 400×400 PNG placeholder and save as managed file ──────
function create_product_image(string $label, int $hue, string $filename): ?File {
  $dir = 'public://products/';
  \Drupal::service('file_system')->prepareDirectory($dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

  $dest = $dir . $filename;
  $full_path = \Drupal::service('file_system')->realpath($dir) . '/' . $filename;

  // Skip if already exists
  if (file_exists($full_path)) {
    $files = \Drupal::entityTypeManager()->getStorage('file')
      ->loadByProperties(['uri' => $dest]);
    if ($files) {
      return reset($files);
    }
  }

  // HSV → RGB
  $h = $hue / 360;
  $s = 0.55; $v = 0.92;
  $i = (int)($h * 6);
  $f = $h * 6 - $i;
  $p = $v * (1 - $s);
  $q = $v * (1 - $f * $s);
  $t = $v * (1 - (1 - $f) * $s);
  switch ($i % 6) {
    case 0: [$r,$g,$b] = [$v,$t,$p]; break;
    case 1: [$r,$g,$b] = [$q,$v,$p]; break;
    case 2: [$r,$g,$b] = [$p,$v,$t]; break;
    case 3: [$r,$g,$b] = [$p,$q,$v]; break;
    case 4: [$r,$g,$b] = [$t,$p,$v]; break;
    default:[$r,$g,$b] = [$v,$p,$q]; break;
  }
  $r = (int)($r*255); $g = (int)($g*255); $b = (int)($b*255);

  $img  = imagecreatetruecolor(400, 400);
  $bg   = imagecolorallocate($img, $r, $g, $b);
  $bg2  = imagecolorallocate($img, max(0,$r-40), max(0,$g-40), max(0,$b-40));
  $white= imagecolorallocate($img, 255, 255, 255);
  $dark = imagecolorallocate($img, 40, 40, 60);

  // Background gradient approximation
  imagefilledrectangle($img, 0, 0, 400, 400, $bg);
  imagefilledrectangle($img, 0, 200, 400, 400, $bg2);

  // Product icon silhouette (simple rounded rect)
  imagefilledroundedrectangle($img, 120, 60, 280, 300, 20, $white);

  // Camera dot (top)
  imagefilledellipse($img, 200, 90, 30, 30, $bg2);
  imagefilledellipse($img, 200, 90, 18, 18, $dark);

  // Screen area
  imagefilledroundedrectangle($img, 135, 105, 265, 285, 5, $dark);
  $screen = imagecolorallocatealpha($img, 30, 144, 255, 60);
  imagefilledroundedrectangle($img, 138, 108, 262, 282, 4, $screen);

  // Home button
  imagefilledellipse($img, 200, 320, 35, 35, $white);
  imagefilledellipse($img, 200, 320, 25, 25, $bg2);

  // Product name text (wrap at ~18 chars)
  $words = explode(' ', $label);
  $lines = []; $line = '';
  foreach ($words as $w) {
    if (strlen($line . ' ' . $w) > 18 && $line !== '') {
      $lines[] = $line; $line = $w;
    } else {
      $line = $line === '' ? $w : $line . ' ' . $w;
    }
  }
  $lines[] = $line;

  $y = 345;
  foreach ($lines as $ln) {
    $tw = (int)(imagefontwidth(3) * strlen($ln));
    imagestring($img, 3, (400 - $tw) / 2, $y, $ln, $white);
    $y += 18;
  }

  imagepng($img, $full_path);
  imagedestroy($img);

  // Register as Drupal managed file
  $file = File::create([
    'uri'    => $dest,
    'status' => 1,
    'uid'    => 1,
  ]);
  $file->save();
  return $file;
}

// Polyfill imagefilledroundedrectangle if not available
if (!function_exists('imagefilledroundedrectangle')) {
  function imagefilledroundedrectangle($img, $x1, $y1, $x2, $y2, $r, $color) {
    imagefilledrectangle($img, $x1+$r, $y1, $x2-$r, $y2, $color);
    imagefilledrectangle($img, $x1, $y1+$r, $x2, $y2-$r, $color);
    imagefilledellipse($img, $x1+$r, $y1+$r, $r*2, $r*2, $color);
    imagefilledellipse($img, $x2-$r, $y1+$r, $r*2, $r*2, $color);
    imagefilledellipse($img, $x1+$r, $y2-$r, $r*2, $r*2, $color);
    imagefilledellipse($img, $x2-$r, $y2-$r, $r*2, $r*2, $color);
  }
}

// ── Helper: create product + variation ───────────────────────────────────────
function make_product(array $data): void {
  // Skip if already exists
  $existing = \Drupal::entityTypeManager()->getStorage('commerce_product')
    ->loadByProperties(['title' => $data['title']]);
  if ($existing) {
    echo "  SKIP (exists): " . $data['title'] . "\n";
    return;
  }

  $store = \Drupal::entityTypeManager()->getStorage('commerce_store')
    ->loadDefault();

  $variation = ProductVariation::create([
    'type'   => 'default',
    'sku'    => $data['sku'],
    'price'  => new \Drupal\commerce_price\Price((string)$data['price'], 'USD'),
    'status' => 1,
  ]);
  $variation->save();

  $product_data = [
    'type'       => 'default',
    'title'      => $data['title'],
    'status'     => 1,
    'uid'        => 1,
    'stores'     => [$store],
    'variations' => [$variation],
    'field_category' => [['target_id' => $data['tid']]],
  ];

  if (!empty($data['tagline'])) {
    $product_data['field_tagline'] = $data['tagline'];
  }
  if (!empty($data['file'])) {
    $product_data['field_product_image'] = [
      'target_id' => $data['file']->id(),
      'alt'       => $data['title'],
    ];
  }

  $product = Product::create($product_data);
  $product->save();
  echo "  Created PID " . $product->id() . ": " . $data['title'] . " — $" . number_format($data['price'], 2) . "\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// PRODUCT DATA
// Category TIDs: Tablets=5, Phones=6, Laptops=7, Audio=8
// ═══════════════════════════════════════════════════════════════════════════

$catalog = [

  // ── PHONES (TID 6) — 10 products ────────────────────────────────────────
  ['tid'=>6,'hue'=>210,'sku'=>'PHN-003','price'=>699.00,
   'title'=>'Google Pixel 9 Pro',
   'tagline'=>'Powered by Google AI — the smartest Pixel yet.'],

  ['tid'=>6,'hue'=>15,'sku'=>'PHN-004','price'=>449.00,
   'title'=>'Samsung Galaxy A55 5G',
   'tagline'=>'Premium features, budget-friendly price.'],

  ['tid'=>6,'hue'=>120,'sku'=>'PHN-005','price'=>799.00,
   'title'=>'OnePlus 12 Pro',
   'tagline'=>'Hasselblad tuned cameras, 100W fast charging.'],

  ['tid'=>6,'hue'=>280,'sku'=>'PHN-006','price'=>1099.00,
   'title'=>'Sony Xperia 1 VI',
   'tagline'=>'4K OLED display and pro-grade camera system.'],

  ['tid'=>6,'hue'=>0,'sku'=>'PHN-007','price'=>599.00,
   'title'=>'Motorola Edge 50 Ultra',
   'tagline'=>'Ultra-thin design with 125W turbo charging.'],

  ['tid'=>6,'hue'=>55,'sku'=>'PHN-008','price'=>899.00,
   'title'=>'Xiaomi 14 Ultra',
   'tagline'=>'Leica optics with 1-inch periscope sensor.'],

  ['tid'=>6,'hue'=>180,'sku'=>'PHN-009','price'=>299.00,
   'title'=>'Nokia G60 5G',
   'tagline'=>'3 years of OS updates, 3 years of security patches.'],

  ['tid'=>6,'hue'=>330,'sku'=>'PHN-010','price'=>649.00,
   'title'=>'Realme GT 6 Pro',
   'tagline'=>'Snapdragon 8 Gen 3, 5500mAh battery.'],

  ['tid'=>6,'hue'=>200,'sku'=>'PHN-011','price'=>849.00,
   'title'=>'Vivo X100 Pro',
   'tagline'=>'ZEISS T* optics, 100W wireless charging.'],

  ['tid'=>6,'hue'=>90,'sku'=>'PHN-012','price'=>479.00,
   'title'=>'OPPO Reno 12 Pro',
   'tagline'=>'AI-powered portrait photography at its finest.'],

  // ── TABLETS (TID 5) — 4 products ────────────────────────────────────────
  ['tid'=>5,'hue'=>240,'sku'=>'TAB-002','price'=>1099.00,
   'title'=>'Samsung Galaxy Tab S9 Ultra',
   'tagline'=>'14.6" Dynamic AMOLED with S Pen included.'],

  ['tid'=>5,'hue'=>200,'sku'=>'TAB-003','price'=>1599.00,
   'title'=>'Microsoft Surface Pro 11',
   'tagline'=>'Full Windows 11, Copilot+, all-day battery life.'],

  ['tid'=>5,'hue'=>30,'sku'=>'TAB-004','price'=>349.00,
   'title'=>'Lenovo Tab P12 Pro',
   'tagline'=>'12.6" AMOLED, Dolby Atmos stereo speakers.'],

  ['tid'=>5,'hue'=>160,'sku'=>'TAB-005','price'=>499.00,
   'title'=>'Amazon Fire Max 13',
   'tagline'=>'Perfect for streaming, gaming, and productivity.'],

  // ── LAPTOPS (TID 7) — 4 products ────────────────────────────────────────
  ['tid'=>7,'hue'=>220,'sku'=>'LPT-003','price'=>1699.00,
   'title'=>'HP Spectre x360 14',
   'tagline'=>'2-in-1 OLED convertible with Intel Core Ultra.'],

  ['tid'=>7,'hue'=>25,'sku'=>'LPT-004','price'=>1899.00,
   'title'=>'Lenovo ThinkPad X1 Carbon Gen 12',
   'tagline'=>'Ultra-light business laptop, 1.12 kg, MIL-SPEC tested.'],

  ['tid'=>7,'hue'=>155,'sku'=>'LPT-005','price'=>1499.00,
   'title'=>'ASUS ROG Zephyrus G14',
   'tagline'=>'AMD Ryzen 9 + RTX 4060, gaming in 1.65 kg.'],

  ['tid'=>7,'hue'=>270,'sku'=>'LPT-006','price'=>2199.00,
   'title'=>'Apple MacBook Pro 16" M4',
   'tagline'=>'M4 Pro chip, Liquid Retina XDR, 22hr battery.'],

  // ── AUDIO (TID 8) — 4 products ───────────────────────────────────────────
  ['tid'=>8,'hue'=>45,'sku'=>'AUD-002','price'=>329.00,
   'title'=>'Bose QuietComfort Ultra',
   'tagline'=>'Immersive spatial audio, world-class ANC.'],

  ['tid'=>8,'hue'=>190,'sku'=>'AUD-003','price'=>249.00,
   'title'=>'Apple AirPods Pro 2',
   'tagline'=>'Adaptive Transparency, H2 chip, USB-C charging.'],

  ['tid'=>8,'hue'=>0,'sku'=>'AUD-004','price'=>449.00,
   'title'=>'Jabra Evolve2 85',
   'tagline'=>'Professional UC headset with 8-mic ANC system.'],

  ['tid'=>8,'hue'=>110,'sku'=>'AUD-005','price'=>399.00,
   'title'=>'Sennheiser Momentum 4',
   'tagline'=>'60-hour playtime, adaptive ANC, aptX Adaptive.'],
];

// ── Generate images and create products ──────────────────────────────────────
// Map TID to color hue offset for variety
$category_labels = [5=>'Tablets',6=>'Phones',7=>'Laptops',8=>'Audio'];
$current_tid = 0;

// Get next file index (after existing product-6.png)
$file_index = 7;

foreach ($catalog as $i => $item) {
  if ($item['tid'] !== $current_tid) {
    $current_tid = $item['tid'];
    echo "\n── " . $category_labels[$item['tid']] . " ─────────────────────────────\n";
  }

  $filename = 'product-' . $file_index . '.png';
  $file = create_product_image($item['title'], $item['hue'], $filename);
  $file_index++;

  make_product([
    'tid'     => $item['tid'],
    'sku'     => $item['sku'],
    'price'   => $item['price'],
    'title'   => $item['title'],
    'tagline' => $item['tagline'],
    'file'    => $file,
  ]);
}

echo "\n✓ Done! Created " . count($catalog) . " sample products.\n";
echo "  Visit the homepage to see category sections.\n";
echo "  Run 'ddev drush cr' to clear caches.\n";
