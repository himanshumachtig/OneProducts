<?php
/**
 * Copy kids_product_01-50.png from Windows Downloads into Drupal product slots.
 *
 * Screenshot category order:
 *   01-10 → Kids Hoodies  (product-109 … 118)
 *   11-20 → Kids Jeans    (product-89  … 98)
 *   21-30 → Kids Shoes    (product-119 … 128)
 *   31-40 → Kids Shorts   (product-99  … 108)
 *   41-50 → Tshirts/Kids T-Shirts (product-79 … 88)
 *
 * Each image is cover-cropped to 400×400 PNG before saving.
 */

$src_dir  = '/var/www/html/kids_images';
$dest_dir = \Drupal::service('file_system')->realpath('public://products/');

// Map: source file number → destination product-N.png number
$mapping = [];

// Hoodies 01-10 → product-109 … 118
for ($i = 1; $i <= 10; $i++) {
  $mapping[str_pad($i, 2, '0', STR_PAD_LEFT)] = 108 + $i;  // 109…118
}
// Jeans 11-20 → product-89 … 98
for ($i = 11; $i <= 20; $i++) {
  $mapping[str_pad($i, 2, '0', STR_PAD_LEFT)] = 78 + ($i - 10);  // 89…98
}
// Shoes 21-30 → product-119 … 128
for ($i = 21; $i <= 30; $i++) {
  $mapping[str_pad($i, 2, '0', STR_PAD_LEFT)] = 118 + ($i - 20);  // 119…128
}
// Shorts 31-40 → product-99 … 108
for ($i = 31; $i <= 40; $i++) {
  $mapping[str_pad($i, 2, '0', STR_PAD_LEFT)] = 98 + ($i - 30);   // 99…108
}
// Tshirts 41-50 → product-79 … 88
for ($i = 41; $i <= 50; $i++) {
  $mapping[str_pad($i, 2, '0', STR_PAD_LEFT)] = 78 + ($i - 40);   // 79…88
}

// ── Cover-crop helper ──────────────────────────────────────────────────────────
function copy_and_resize(string $src, string $dest): bool {
  $data = file_get_contents($src);
  if (!$data) { echo "    [FAIL] cannot read $src\n"; return false; }

  $original = @imagecreatefromstring($data);
  if (!$original) {
    // Try loading as PNG/JPEG directly
    $original = @imagecreatefrompng($src) ?: @imagecreatefromjpeg($src);
  }
  if (!$original) { echo "    [FAIL] cannot decode image $src\n"; return false; }

  $sw = imagesx($original);
  $sh = imagesy($original);

  // Cover-crop: scale so shortest side = 400, then centre-crop to 400×400
  $scale = max(400 / $sw, 400 / $sh);
  $nw    = (int)round($sw * $scale);
  $nh    = (int)round($sh * $scale);
  $ox    = (int)(($nw - 400) / 2);
  $oy    = (int)(($nh - 400) / 2);

  $tmp = imagecreatetruecolor($nw, $nh);
  // Preserve transparency
  imagealphablending($tmp, false);
  imagesavealpha($tmp, true);
  $trans = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
  imagefill($tmp, 0, 0, $trans);
  imagecopyresampled($tmp, $original, 0, 0, 0, 0, $nw, $nh, $sw, $sh);

  $out = imagecreatetruecolor(400, 400);
  // White background for transparency
  $white = imagecolorallocate($out, 255, 255, 255);
  imagefill($out, 0, 0, $white);
  imagecopy($out, $tmp, 0, 0, $ox, $oy, 400, 400);

  imagedestroy($tmp);
  imagedestroy($original);

  $ok = imagepng($out, $dest);
  imagedestroy($out);
  return $ok;
}

// ── Category label for display ────────────────────────────────────────────────
function category_label(int $product_num): string {
  if ($product_num >= 79  && $product_num <= 88)  return 'Kids T-Shirts';
  if ($product_num >= 89  && $product_num <= 98)  return 'Kids Jeans';
  if ($product_num >= 99  && $product_num <= 108) return 'Kids Shorts';
  if ($product_num >= 109 && $product_num <= 118) return 'Kids Hoodies';
  if ($product_num >= 119 && $product_num <= 128) return 'Kids Shoes';
  return 'Unknown';
}

// ── Main loop ─────────────────────────────────────────────────────────────────
echo "\nCopying Kids product images …\n";
echo "Source : $src_dir\n";
echo "Dest   : $dest_dir\n\n";

$ok = 0; $fail = 0;
ksort($mapping);

foreach ($mapping as $src_num => $dest_num) {
  $src_file  = "$src_dir/kids_product_{$src_num}.png";
  $dest_file = "$dest_dir/product-{$dest_num}.png";
  $cat       = category_label($dest_num);

  echo "[$src_num → product-{$dest_num}] $cat\n";

  if (!file_exists($src_file)) {
    echo "    [MISSING] $src_file\n";
    $fail++;
    continue;
  }

  if (copy_and_resize($src_file, $dest_file)) {
    echo "    ✓ saved product-{$dest_num}.png\n";
    $ok++;
  } else {
    $fail++;
  }
}

// ── Flush Drupal image style caches ───────────────────────────────────────────
echo "\nFlushing image style caches …\n";
foreach (\Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple() as $style) {
  $style->flush();
  echo '  flushed: ' . $style->id() . "\n";
}

echo "\n✓ Done — $ok copied, $fail failed.\n";
echo "  Run: ddev drush cr\n";
