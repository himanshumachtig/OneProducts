<?php
/**
 * Switch store currency to INR and set price + list_price (MRP) on all products.
 *
 * list_price = MRP (original/crossed-out price)
 * price      = selling price (discounted)
 */

// ── 1. Switch store default currency to INR ───────────────────────────────────
$store_storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
foreach ($store_storage->loadMultiple() as $store) {
  $store->set('default_currency', 'INR');
  $store->save();
  echo "Store '" . $store->label() . "' → INR\n";
}

// ── 2. Price table: [product_num => [selling_price, list_price/MRP]] ──────────
// Men's Jeans (product-29 … 38)
$prices = [
  29 => [1499, 2999],  // Men's Slim Fit Dark Wash Jeans
  30 => [1299, 2499],  // Men's Relaxed Fit Straight Jeans
  31 => [1599, 2999],  // Men's Skinny Stretch Jeans
  32 => [1399, 2799],  // Men's Classic Bootcut Jeans
  33 => [999,  1999],  // Men's Distressed Ripped Jeans
  34 => [1299, 2499],  // Men's Tapered Light Wash Jeans
  35 => [1699, 3299],  // Men's Multi-Pocket Cargo Jeans
  36 => [1199, 2199],  // Men's Jogger-Style Denim Jeans
  37 => [2499, 4499],  // Men's Raw Selvedge Straight Jeans
  38 => [1399, 2599],  // Men's Wide Leg Denim Jeans

  // Men's T-Shirts (product-39 … 48)
  39 => [399,  799],   // Men's Classic Crew Neck T-Shirt
  40 => [499,  999],   // Men's Graphic Print T-Shirt
  41 => [349,  699],   // Men's V-Neck Slim T-Shirt
  42 => [449,  899],   // Men's Pocket Chest T-Shirt
  43 => [549,  999],   // Men's Long Sleeve T-Shirt
  44 => [499,  999],   // Men's Henley Button T-Shirt
  45 => [449,  849],   // Men's Breton Stripe T-Shirt
  46 => [599,  1199],  // Men's Performance Dry-Fit T-Shirt
  47 => [499,  999],   // Men's Tie-Dye Oversized T-Shirt
  48 => [549,  1099],  // Men's Longline Extended T-Shirt

  // Men's Shirts (product-49 … 58)
  49 => [799,  1599],  // Men's Classic Oxford Button Shirt
  50 => [699,  1399],  // Men's Linen Summer Shirt
  51 => [899,  1799],  // Men's Flannel Check Shirt
  52 => [999,  1999],  // Men's Slim Fit Formal Shirt
  53 => [849,  1699],  // Men's Denim Western Shirt
  54 => [749,  1499],  // Men's Hawaiian Camp Collar Shirt
  55 => [799,  1599],  // Men's Chambray Work Shirt
  56 => [1099, 2199],  // Men's Dobby Texture Formal Shirt
  57 => [899,  1799],  // Men's Mandarin Collar Linen Shirt
  58 => [1299, 2599],  // Men's Twill Overshirt Jacket

  // Men's Jackets (product-59 … 68)
  59 => [3999, 7999],  // Men's Genuine Leather Biker Jacket
  60 => [2499, 4999],  // Men's Quilted Puffer Jacket
  61 => [1999, 3999],  // Men's Lightweight Bomber Jacket
  62 => [4999, 9999],  // Men's Wool-Cashmere Overcoat
  63 => [2999, 5999],  // Men's Waxed Cotton Field Jacket
  64 => [1499, 2999],  // Men's Lightweight Windbreaker
  65 => [1799, 3499],  // Men's Softshell Fleece Jacket
  66 => [2499, 4999],  // Men's Varsity College Jacket
  67 => [2799, 5499],  // Men's Military Cargo Jacket
  68 => [1999, 3999],  // Men's Denim Trucker Jacket

  // Men's Shoes (product-69 … 78)
  69 => [1999, 3999],  // Men's Clean White Leather Sneakers
  70 => [2999, 5999],  // Men's Suede Chelsea Boots
  71 => [1799, 3499],  // Men's Road Running Trainers
  72 => [3499, 6999],  // Men's Oxford Dress Shoes
  73 => [1499, 2999],  // Men's Canvas High-Top Sneakers
  74 => [2499, 4999],  // Men's Leather Penny Loafers
  75 => [3999, 7999],  // Men's Waterproof Hiking Boots
  76 => [999,  1999],  // Men's Jute Canvas Espadrilles
  77 => [3299, 6499],  // Men's Wingtip Derby Brogues
  78 => [2199, 4299],  // Men's Chunky Platform Sneakers

  // Kids' T-Shirts (product-79 … 88)
  79 => [299,  599],   // Kids' Dinosaur Print T-Shirt
  80 => [349,  699],   // Kids' Superhero Graphic Tee
  81 => [299,  599],   // Kids' Rainbow Stripe T-Shirt
  82 => [349,  699],   // Kids' Space Explorer T-Shirt
  83 => [299,  599],   // Kids' Animal Print T-Shirt
  84 => [349,  699],   // Kids' Tie-Dye Effect T-Shirt
  85 => [399,  799],   // Kids' Sports Number T-Shirt
  86 => [249,  499],   // Kids' Plain Essential T-Shirt
  87 => [449,  899],   // Kids' Long Sleeve T-Shirt
  88 => [299,  599],   // Kids' Pocket T-Shirt

  // Kids' Jeans (product-89 … 98)
  89 => [799,  1599],  // Kids' Slim Fit Dark Wash Jeans
  90 => [699,  1399],  // Kids' Elasticated Waist Jeans
  91 => [749,  1499],  // Kids' Ripped Knee Jeans
  92 => [799,  1599],  // Kids' Skinny Stretch Jeans
  93 => [699,  1399],  // Kids' Jogger Denim Jeans
  94 => [649,  1299],  // Kids' Light Wash Straight Jeans
  95 => [849,  1699],  // Kids' Cargo Denim Jeans
  96 => [599,  1199],  // Kids' Pull-On Jeggings
  97 => [999,  1999],  // Kids' Dungaree Denim Overalls
  98 => [749,  1499],  // Kids' Wide Leg Barrel Jeans

  // Kids' Shorts (product-99 … 108)
  99  => [299, 599],   // Kids' Jersey Sport Shorts
  100 => [499, 999],   // Kids' Denim Cutoff Shorts
  101 => [399, 799],   // Kids' Quick-Dry Swim Shorts
  102 => [549, 1099],  // Kids' Cargo Shorts
  103 => [349, 699],   // Kids' Floral Print Shorts
  104 => [449, 899],   // Kids' Chino Shorts
  105 => [299, 599],   // Kids' Cycling Shorts
  106 => [349, 699],   // Kids' Tie-Dye Jersey Shorts
  107 => [399, 799],   // Kids' Linen Blend Shorts
  108 => [299, 599],   // Kids' Roll-Hem Jersey Shorts

  // Kids' Hoodies (product-109 … 118)
  109 => [799,  1599],  // Kids' Rainbow Zip-Up Hoodie
  110 => [699,  1399],  // Kids' Camo Pullover Hoodie
  111 => [749,  1499],  // Kids' Character Print Hoodie
  112 => [849,  1699],  // Kids' Fleece-Lined Pullover Hoodie
  113 => [699,  1399],  // Kids' Tie-Dye Pullover Hoodie
  114 => [799,  1599],  // Kids' Galaxy Print Hoodie
  115 => [749,  1499],  // Kids' Varsity Logo Hoodie
  116 => [999,  1999],  // Kids' Windproof Outdoor Hoodie
  117 => [699,  1399],  // Kids' Striped Pullover Hoodie
  118 => [899,  1799],  // Kids' Sherpa Fleece Hoodie

  // Kids' Shoes (product-119 … 128)
  119 => [799,  1599],  // Kids' Canvas Lace-Up Sneakers
  120 => [699,  1399],  // Kids' Velcro Strap Trainers
  121 => [999,  1999],  // Kids' Wellington Rain Boots
  122 => [1299, 2599],  // Kids' Leather School Shoes
  123 => [899,  1799],  // Kids' Sport Running Shoes
  124 => [999,  1999],  // Kids' Light-Up LED Trainers
  125 => [1199, 2399],  // Kids' Chelsea Boots
  126 => [549,  1099],  // Kids' Slip-On Summer Sandals
  127 => [1299, 2599],  // Kids' Winter Snow Boots
  128 => [899,  1799],  // Kids' Glitter High-Top Sneakers
];

// ── 3. Update all product variations ─────────────────────────────────────────
// Variation IDs 30-129 correspond to products 29-128 (variation_id - 1 = product_num)
$variation_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
$variations = $variation_storage->loadMultiple();

$ok = 0; $skip = 0;

foreach ($variations as $variation) {
  $vid = (int)$variation->id();
  $num = $vid - 1;  // variation 30 → product-29, variation 129 → product-128

  if (!isset($prices[$num])) { $skip++; continue; }

  [$sell, $mrp] = $prices[$num];

  $variation->set('price', new \Drupal\commerce_price\Price((string)$sell, 'INR'));
  $variation->set('list_price', new \Drupal\commerce_price\Price((string)$mrp, 'INR'));
  $variation->save();

  echo "product-$num (var-$vid / " . $variation->getSku() . "): ₹$sell (MRP ₹$mrp)\n";
  $ok++;
}

echo "\n✓ Updated: $ok variations, skipped: $skip\n";
echo "  Run: ddev drush cr\n";
