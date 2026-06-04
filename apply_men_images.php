<?php
/**
 * Copy men_product_01-50.png into Drupal product image slots.
 *
 * Men's category order (matches product creation script):
 *   01-10 → Men's Jeans    (product-29  … 38)
 *   11-20 → Men's T-Shirts (product-39  … 48)
 *   21-30 → Men's Shirts   (product-49  … 58)
 *   31-40 → Men's Jackets  (product-59  … 68)
 *   41-50 → Men's Shoes    (product-69  … 78)
 *
 * Each image is cover-cropped to 400×400 PNG before saving.
 */

$src_dir  = '/var/www/html/men_images';
$dest_dir = \Drupal::service('file_system')->realpath('public://products/');

// Map: source file number (zero-padded) → destination product-N slot
$mapping = [];

// Jeans 01-10 → product-29 … 38
for ($i = 1; $i <= 10; $i++) {
  $mapping[str_pad($i, 2, '0', STR_PAD_LEFT)] = 28 + $i;   // 29…38
}
// T-Shirts 11-20 → product-39 … 48
for ($i = 11; $i <= 20; $i++) {
  $mapping[str_pad($i, 2, '0', STR_PAD_LEFT)] = 28 + $i;   // 39…48
}
// Shirts 21-30 → product-49 … 58
for ($i = 21; $i <= 30; $i++) {
  $mapping[str_pad($i, 2, '0', STR_PAD_LEFT)] = 28 + $i;   // 49…58
}
// Jackets 31-40 → product-59 … 68
for ($i = 31; $i <= 40; $i++) {
  $mapping[str_pad($i, 2, '0', STR_PAD_LEFT)] = 28 + $i;   // 59…68
}
// Shoes 41-50 → product-69 … 78
for ($i = 41; $i <= 50; $i++) {
  $mapping[str_pad($i, 2, '0', STR_PAD_LEFT)] = 28 + $i;   // 69…78
}

// ── Cover-crop to 400×400 ─────────────────────────────────────────────────────
function copy_and_resize(string $src, string $dest): bool {
  $data = file_get_contents($src);
  if (!$data) { echo "    [FAIL] cannot read $src\n"; return false; }

  $original = @imagecreatefromstring($data);
  if (!$original) {
    $original = @imagecreatefrompng($src) ?: @imagecreatefromjpeg($src);
  }
  if (!$original) { echo "    [FAIL] cannot decode $src\n"; return false; }

  $sw = imagesx($original);
  $sh = imagesy($original);

  $scale = max(400 / $sw, 400 / $sh);
  $nw    = (int)round($sw * $scale);
  $nh    = (int)round($sh * $scale);
  $ox    = (int)(($nw - 400) / 2);
  $oy    = (int)(($nh - 400) / 2);

  $tmp = imagecreatetruecolor($nw, $nh);
  imagealphablending($tmp, false);
  imagesavealpha($tmp, true);
  $trans = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
  imagefill($tmp, 0, 0, $trans);
  imagecopyresampled($tmp, $original, 0, 0, 0, 0, $nw, $nh, $sw, $sh);

  $out   = imagecreatetruecolor(400, 400);
  $white = imagecolorallocate($out, 255, 255, 255);
  imagefill($out, 0, 0, $white);
  imagecopy($out, $tmp, 0, 0, $ox, $oy, 400, 400);

  imagedestroy($tmp);
  imagedestroy($original);

  $ok = imagepng($out, $dest);
  imagedestroy($out);
  return $ok;
}

// ── Category label helper ─────────────────────────────────────────────────────
function category_label(int $n): string {
  if ($n >= 29 && $n <= 38) return "Men's Jeans";
  if ($n >= 39 && $n <= 48) return "Men's T-Shirts";
  if ($n >= 49 && $n <= 58) return "Men's Shirts";
  if ($n >= 59 && $n <= 68) return "Men's Jackets";
  if ($n >= 69 && $n <= 78) return "Men's Shoes";
  return 'Unknown';
}

// ── Main ──────────────────────────────────────────────────────────────────────
echo "\nApplying Men's product images …\n";
echo "Source : $src_dir\n";
echo "Dest   : $dest_dir\n\n";

$ok = 0; $fail = 0;
ksort($mapping);

foreach ($mapping as $src_num => $dest_num) {
  $src_file  = "$src_dir/men_product_{$src_num}.png";
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

// ── Flush image style caches ──────────────────────────────────────────────────
echo "\nFlushing image style caches …\n";
foreach (\Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple() as $style) {
  $style->flush();
  echo '  flushed: ' . $style->id() . "\n";
}

echo "\n✓ Done — $ok copied, $fail failed.\n";
echo "  Run: ddev drush cr\n";
