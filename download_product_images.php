<?php
/**
 * Download real clothing photos from LoremFlickr (Creative Commons licensed).
 * Overwrites product-29.png → product-128.png with actual product photos.
 *
 * LoremFlickr: https://loremflickr.com — free CC-licensed stock images by keyword.
 * The ?lock=N parameter pins each URL to the same photo on every request.
 *
 * Run: ddev drush php-script download_product_images.php
 */

/**
 * Download one image via cURL, resize to 400×400, save as PNG.
 */
function download_and_save(string $url, string $dest_path): bool {
  // Fetch with cURL (follow redirects, 20s timeout)
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 6,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; DrupalDev/1.0)',
    CURLOPT_SSL_VERIFYPEER => false,
  ]);
  $data = curl_exec($ch);
  $http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  curl_close($ch);

  if (!$data || $http !== 200) {
    echo "    [FAIL] HTTP $http — $url\n";
    return false;
  }

  // Load image from downloaded bytes
  $src = @imagecreatefromstring($data);
  if (!$src) {
    echo "    [FAIL] Could not decode image from $url\n";
    return false;
  }

  // Scale / crop to 400×400
  $sw = imagesx($src);
  $sh = imagesy($src);
  $dst = imagecreatetruecolor(400, 400);

  // Cover crop: scale so shortest side = 400, then centre-crop
  $scale = max(400 / $sw, 400 / $sh);
  $nw = (int)round($sw * $scale);
  $nh = (int)round($sh * $scale);
  $ox = (int)(($nw - 400) / 2);
  $oy = (int)(($nh - 400) / 2);

  $tmp = imagecreatetruecolor($nw, $nh);
  imagecopyresampled($tmp, $src, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
  imagecopy($dst, $tmp, 0, 0, $ox, $oy, 400, 400);
  imagedestroy($tmp);
  imagedestroy($src);

  $ok = imagepng($dst, $dest_path);
  imagedestroy($dst);
  return $ok;
}

// ─── Keyword map per product type ────────────────────────────────────────────
// LoremFlickr URL: https://loremflickr.com/400/400/{keywords}?lock={N}
// Different lock numbers give different photos for the same keyword.
$keywords = [
  'jeans_men'   => 'mens,jeans,denim',
  'jeans_kids'  => 'kids,jeans,denim',
  'tshirt_men'  => 'mens,tshirt,fashion',
  'tshirt_kids' => 'kids,tshirt,children',
  'shirt_men'   => 'mens,shirt,fashion',
  'jacket_men'  => 'mens,jacket,fashion',
  'shoes_men'   => 'mens,shoes,footwear',
  'shoes_kids'  => 'kids,shoes,footwear',
  'shorts_kids' => 'kids,shorts,summer',
  'hoodie_kids' => 'kids,hoodie,sweatshirt',
];

// ─── Catalog: [keyword_key, lock_number, label] ──────────────────────────────
// lock numbers are unique across the whole catalog so every product
// gets a distinct photo from LoremFlickr.
$catalog = [
  // ── Men's Jeans (product-29 … 38) ───────────────────────────────────────
  ['jeans_men', 1,  "Men's Slim Fit Dark Wash Jeans"],
  ['jeans_men', 2,  "Men's Relaxed Fit Straight Jeans"],
  ['jeans_men', 3,  "Men's Skinny Stretch Jeans"],
  ['jeans_men', 4,  "Men's Classic Bootcut Jeans"],
  ['jeans_men', 5,  "Men's Distressed Ripped Jeans"],
  ['jeans_men', 6,  "Men's Tapered Light Wash Jeans"],
  ['jeans_men', 7,  "Men's Multi-Pocket Cargo Jeans"],
  ['jeans_men', 8,  "Men's Jogger-Style Denim Jeans"],
  ['jeans_men', 9,  "Men's Raw Selvedge Straight Jeans"],
  ['jeans_men', 10, "Men's Wide Leg Denim Jeans"],

  // ── Men's T-Shirts (39 … 48) ────────────────────────────────────────────
  ['tshirt_men', 11, "Men's Classic Crew Neck T-Shirt"],
  ['tshirt_men', 12, "Men's Graphic Print T-Shirt"],
  ['tshirt_men', 13, "Men's V-Neck Slim T-Shirt"],
  ['tshirt_men', 14, "Men's Pocket Chest T-Shirt"],
  ['tshirt_men', 15, "Men's Long Sleeve T-Shirt"],
  ['tshirt_men', 16, "Men's Henley Button T-Shirt"],
  ['tshirt_men', 17, "Men's Breton Stripe T-Shirt"],
  ['tshirt_men', 18, "Men's Performance Dry-Fit T-Shirt"],
  ['tshirt_men', 19, "Men's Tie-Dye Oversized T-Shirt"],
  ['tshirt_men', 20, "Men's Longline Extended T-Shirt"],

  // ── Men's Shirts (49 … 58) ──────────────────────────────────────────────
  ['shirt_men', 21, "Men's Classic Oxford Button Shirt"],
  ['shirt_men', 22, "Men's Linen Summer Shirt"],
  ['shirt_men', 23, "Men's Flannel Check Shirt"],
  ['shirt_men', 24, "Men's Slim Fit Formal Shirt"],
  ['shirt_men', 25, "Men's Denim Western Shirt"],
  ['shirt_men', 26, "Men's Hawaiian Camp Collar Shirt"],
  ['shirt_men', 27, "Men's Chambray Work Shirt"],
  ['shirt_men', 28, "Men's Dobby Texture Formal Shirt"],
  ['shirt_men', 29, "Men's Mandarin Collar Linen Shirt"],
  ['shirt_men', 30, "Men's Twill Overshirt Jacket"],

  // ── Men's Jackets (59 … 68) ─────────────────────────────────────────────
  ['jacket_men', 31, "Men's Genuine Leather Biker Jacket"],
  ['jacket_men', 32, "Men's Quilted Puffer Jacket"],
  ['jacket_men', 33, "Men's Lightweight Bomber Jacket"],
  ['jacket_men', 34, "Men's Wool-Cashmere Overcoat"],
  ['jacket_men', 35, "Men's Waxed Cotton Field Jacket"],
  ['jacket_men', 36, "Men's Lightweight Windbreaker"],
  ['jacket_men', 37, "Men's Softshell Fleece Jacket"],
  ['jacket_men', 38, "Men's Varsity College Jacket"],
  ['jacket_men', 39, "Men's Military Cargo Jacket"],
  ['jacket_men', 40, "Men's Denim Trucker Jacket"],

  // ── Men's Shoes (69 … 78) ───────────────────────────────────────────────
  ['shoes_men', 41, "Men's Clean White Leather Sneakers"],
  ['shoes_men', 42, "Men's Suede Chelsea Boots"],
  ['shoes_men', 43, "Men's Road Running Trainers"],
  ['shoes_men', 44, "Men's Oxford Dress Shoes"],
  ['shoes_men', 45, "Men's Canvas High-Top Sneakers"],
  ['shoes_men', 46, "Men's Leather Penny Loafers"],
  ['shoes_men', 47, "Men's Waterproof Hiking Boots"],
  ['shoes_men', 48, "Men's Jute Canvas Espadrilles"],
  ['shoes_men', 49, "Men's Wingtip Derby Brogues"],
  ['shoes_men', 50, "Men's Chunky Platform Sneakers"],

  // ── Kids' T-Shirts (79 … 88) ────────────────────────────────────────────
  ['tshirt_kids', 51, "Kids' Dinosaur Print T-Shirt"],
  ['tshirt_kids', 52, "Kids' Superhero Graphic Tee"],
  ['tshirt_kids', 53, "Kids' Rainbow Stripe T-Shirt"],
  ['tshirt_kids', 54, "Kids' Space Explorer T-Shirt"],
  ['tshirt_kids', 55, "Kids' Animal Print T-Shirt"],
  ['tshirt_kids', 56, "Kids' Tie-Dye Effect T-Shirt"],
  ['tshirt_kids', 57, "Kids' Sports Number T-Shirt"],
  ['tshirt_kids', 58, "Kids' Plain Essential T-Shirt"],
  ['tshirt_kids', 59, "Kids' Long Sleeve T-Shirt"],
  ['tshirt_kids', 60, "Kids' Pocket T-Shirt"],

  // ── Kids' Jeans (89 … 98) ───────────────────────────────────────────────
  ['jeans_kids', 61, "Kids' Slim Fit Dark Wash Jeans"],
  ['jeans_kids', 62, "Kids' Elasticated Waist Jeans"],
  ['jeans_kids', 63, "Kids' Ripped Knee Jeans"],
  ['jeans_kids', 64, "Kids' Skinny Stretch Jeans"],
  ['jeans_kids', 65, "Kids' Jogger Denim Jeans"],
  ['jeans_kids', 66, "Kids' Light Wash Straight Jeans"],
  ['jeans_kids', 67, "Kids' Cargo Denim Jeans"],
  ['jeans_kids', 68, "Kids' Pull-On Jeggings"],
  ['jeans_kids', 69, "Kids' Dungaree Denim Overalls"],
  ['jeans_kids', 70, "Kids' Wide Leg Barrel Jeans"],

  // ── Kids' Shorts (99 … 108) ─────────────────────────────────────────────
  ['shorts_kids', 71, "Kids' Jersey Sport Shorts"],
  ['shorts_kids', 72, "Kids' Denim Cutoff Shorts"],
  ['shorts_kids', 73, "Kids' Quick-Dry Swim Shorts"],
  ['shorts_kids', 74, "Kids' Cargo Shorts"],
  ['shorts_kids', 75, "Kids' Floral Print Shorts"],
  ['shorts_kids', 76, "Kids' Chino Shorts"],
  ['shorts_kids', 77, "Kids' Cycling Shorts"],
  ['shorts_kids', 78, "Kids' Tie-Dye Jersey Shorts"],
  ['shorts_kids', 79, "Kids' Linen Blend Shorts"],
  ['shorts_kids', 80, "Kids' Roll-Hem Jersey Shorts"],

  // ── Kids' Hoodies (109 … 118) ───────────────────────────────────────────
  ['hoodie_kids', 81, "Kids' Rainbow Zip-Up Hoodie"],
  ['hoodie_kids', 82, "Kids' Camo Pullover Hoodie"],
  ['hoodie_kids', 83, "Kids' Character Print Hoodie"],
  ['hoodie_kids', 84, "Kids' Fleece-Lined Pullover Hoodie"],
  ['hoodie_kids', 85, "Kids' Tie-Dye Pullover Hoodie"],
  ['hoodie_kids', 86, "Kids' Galaxy Print Hoodie"],
  ['hoodie_kids', 87, "Kids' Varsity Logo Hoodie"],
  ['hoodie_kids', 88, "Kids' Windproof Outdoor Hoodie"],
  ['hoodie_kids', 89, "Kids' Striped Pullover Hoodie"],
  ['hoodie_kids', 90, "Kids' Sherpa Fleece Hoodie"],

  // ── Kids' Shoes (119 … 128) ─────────────────────────────────────────────
  ['shoes_kids', 91, "Kids' Canvas Lace-Up Sneakers"],
  ['shoes_kids', 92, "Kids' Velcro Strap Trainers"],
  ['shoes_kids', 93, "Kids' Wellington Rain Boots"],
  ['shoes_kids', 94, "Kids' Leather School Shoes"],
  ['shoes_kids', 95, "Kids' Sport Running Shoes"],
  ['shoes_kids', 96, "Kids' Light-Up LED Trainers"],
  ['shoes_kids', 97, "Kids' Chelsea Boots"],
  ['shoes_kids', 98, "Kids' Slip-On Summer Sandals"],
  ['shoes_kids', 99, "Kids' Winter Snow Boots"],
  ['shoes_kids', 100,"Kids' Glitter High-Top Sneakers"],
];

// ─── Download loop ────────────────────────────────────────────────────────────
$real_dir = \Drupal::service('file_system')->realpath('public://products/');
$base     = 'https://loremflickr.com/400/400';

$ok_count   = 0;
$fail_count = 0;
$idx        = 29;

echo "\nDownloading " . count($catalog) . " real clothing photos …\n";
echo "(source: loremflickr.com — Creative Commons licensed)\n\n";

foreach ($catalog as [$key, $lock, $label]) {
  $kw   = $keywords[$key];
  $url  = "{$base}/{$kw}?lock={$lock}";
  $dest = "$real_dir/product-$idx.png";

  echo "[$idx] $label\n    $url\n";

  if (download_and_save($url, $dest)) {
    echo "    → saved product-$idx.png\n";
    $ok_count++;
  } else {
    echo "    → FAILED (keeping existing image)\n";
    $fail_count++;
  }

  $idx++;
  usleep(100000); // 0.1 s delay — be polite to the API
}

// ─── Flush image-style derivatives ───────────────────────────────────────────
echo "\nFlushing Drupal image style caches …\n";
foreach (\Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple() as $s) {
  $s->flush();
  echo "  flushed: " . $s->id() . "\n";
}

echo "\n✓ Done — $ok_count downloaded, $fail_count failed.\n";
echo "  Run: ddev drush cr\n";
