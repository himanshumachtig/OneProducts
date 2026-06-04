<?php
/**
 * Set realistic INR selling price + MRP (list_price) on all remaining products.
 * Variation IDs 1-29 (electronics + Levis) — clothing (30-129) already done.
 *
 * [variation_id] => [sell, mrp]
 */
$prices = [
  // Phones
  1  => [139900, 159900],  // Apple iPhone 15 Pro
  2  => [74999,  84999],   // Samsung Galaxy S24
  7  => [99999,  109999],  // Google Pixel 9 Pro
  8  => [34999,  39999],   // Samsung Galaxy A55 5G
  9  => [69999,  79999],   // OnePlus 12 Pro
  10 => [104990, 119990],  // Sony Xperia 1 VI
  11 => [59999,  69999],   // Motorola Edge 50 Ultra
  12 => [99999,  109999],  // Xiaomi 14 Ultra
  13 => [22999,  26999],   // Nokia G60 5G
  14 => [39999,  44999],   // Realme GT 6 Pro
  15 => [89999,  99999],   // Vivo X100 Pro
  16 => [37999,  42999],   // OPPO Reno 12 Pro

  // Headphones / Audio
  3  => [26990,  32990],   // Sony WH-1000XM5
  25 => [31990,  37990],   // Bose QuietComfort Ultra
  26 => [24900,  29900],   // Apple AirPods Pro 2
  27 => [39990,  46990],   // Jabra Evolve2 85
  28 => [35990,  41990],   // Sennheiser Momentum 4

  // Laptops
  4  => [199900, 229900],  // Apple MacBook Pro 14"
  5  => [149990, 179990],  // Dell XPS 15
  21 => [169990, 189990],  // HP Spectre x360 14
  22 => [179990, 209990],  // Lenovo ThinkPad X1 Carbon Gen 12
  23 => [149990, 169990],  // ASUS ROG Zephyrus G14
  24 => [249900, 279900],  // Apple MacBook Pro 16" M4

  // Tablets / iPad
  6  => [59900,  69900],   // Apple iPad Air
  17 => [108999, 119999],  // Samsung Galaxy Tab S9 Ultra
  18 => [159990, 179990],  // Microsoft Surface Pro 11
  19 => [45990,  54990],   // Lenovo Tab P12 Pro
  20 => [29999,  34999],   // Amazon Fire Max 13

  // Levis (existing product)
  29 => [2999,   4999],    // Levis
];

$variation_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
$ok = 0;

foreach ($prices as $vid => $pair) {
  $variation = $variation_storage->load($vid);
  if (!$variation) { echo "[SKIP] variation $vid not found\n"; continue; }

  [$sell, $mrp] = $pair;

  $variation->set('price',      new \Drupal\commerce_price\Price((string)$sell, 'INR'));
  $variation->set('list_price', new \Drupal\commerce_price\Price((string)$mrp,  'INR'));
  $variation->save();

  $disc = (int)round(($mrp - $sell) / $mrp * 100);
  echo "var-$vid | " . $variation->getSku() . " | ₹$sell (MRP ₹$mrp) — {$disc}% off\n";
  $ok++;
}

echo "\n✓ Updated $ok variations to INR.\n";
echo "  Run: ddev drush cr\n";
