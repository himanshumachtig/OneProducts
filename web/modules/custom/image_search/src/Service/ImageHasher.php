<?php

namespace Drupal\image_search\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\State\StateInterface;

/**
 * Perceptual image hashing service for visual product search.
 *
 * Uses dHash (difference hash) for structural similarity and
 * average RGB for colour similarity, combined 70/30.
 */
class ImageHasher {

  private const STATE_KEY          = 'image_search.hash_index';
  private const THRESHOLD_EXACT   = 16;  // same product, possibly different image source
  private const THRESHOLD_SIMILAR = 22;  // same category / style
  private const COLOR_MIN_EXACT   = 0.87; // strict HSV colour gate for exact tier

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private FileSystemInterface $fileSystem,
    private StateInterface $state,
    private FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  // ── Hashing ────────────────────────────────────────────────────────────────

  /**
   * Compute a 64-bit dHash for an image file.
   * Returns a 64-character string of '0'/'1', or NULL on failure.
   */
  public function dHash(string $path): ?string {
    if (!extension_loaded('gd') || !file_exists($path)) {
      return NULL;
    }
    try {
      $data = @file_get_contents($path);
      if (!$data) return NULL;

      $src = @imagecreatefromstring($data);
      if (!$src) return NULL;

      // Resize to 9×8: each row of 8 comparisons = 64 bits total.
      $dst = imagecreatetruecolor(9, 8);
      imagecopyresampled($dst, $src, 0, 0, 0, 0, 9, 8, imagesx($src), imagesy($src));
      imagefilter($dst, IMG_FILTER_GRAYSCALE);

      $hash = '';
      for ($y = 0; $y < 8; $y++) {
        for ($x = 0; $x < 8; $x++) {
          $left  = imagecolorat($dst, $x,     $y) & 0xFF;
          $right = imagecolorat($dst, $x + 1, $y) & 0xFF;
          $hash .= ($left > $right) ? '1' : '0';
        }
      }
      imagedestroy($src);
      imagedestroy($dst);
      return $hash;
    }
    catch (\Exception) {
      return NULL;
    }
  }

  /**
   * 4-zone colour profile: average RGB per quadrant (top-left, top-right,
   * bottom-left, bottom-right) from a 16×16 thumbnail.
   * Returns flat array of 12 values [r0,g0,b0, r1,g1,b1, r2,g2,b2, r3,g3,b3].
   */
  public function avgRgb(string $path): ?array {
    try {
      $data = @file_get_contents($path);
      if (!$data) return NULL;

      $src = @imagecreatefromstring($data);
      if (!$src) return NULL;

      $dst = imagecreatetruecolor(16, 16);
      imagecopyresampled($dst, $src, 0, 0, 0, 0, 16, 16, imagesx($src), imagesy($src));

      $zones = [[0,0],[8,0],[0,8],[8,8]];
      $result = [];
      foreach ($zones as [$ox, $oy]) {
        $r = $g = $b = $count = 0;
        for ($y = $oy; $y < $oy + 8; $y++) {
          for ($x = $ox; $x < $ox + 8; $x++) {
            $c  = imagecolorat($dst, $x, $y);
            $pr = ($c >> 16) & 0xFF;
            $pg = ($c >> 8)  & 0xFF;
            $pb = $c         & 0xFF;
            // Skip near-white background pixels so product colour dominates.
            if ($pr > 220 && $pg > 220 && $pb > 220) continue;
            $r += $pr; $g += $pg; $b += $pb;
            $count++;
          }
        }
        // If zone is mostly background, fall back to full-zone average.
        if ($count < 4) {
          for ($y = $oy; $y < $oy + 8; $y++) {
            for ($x = $ox; $x < $ox + 8; $x++) {
              $c  = imagecolorat($dst, $x, $y);
              $r += ($c >> 16) & 0xFF;
              $g += ($c >> 8)  & 0xFF;
              $b += $c         & 0xFF;
            }
          }
          $count = 64;
        }
        $result[] = $r / $count;
        $result[] = $g / $count;
        $result[] = $b / $count;
      }
      imagedestroy($src);
      imagedestroy($dst);
      return $result;
    }
    catch (\Exception) {
      return NULL;
    }
  }

  /**
   * Hamming distance between two 64-char binary strings (0 = identical).
   */
  public function hammingDistance(string $a, string $b): int {
    $d = 0;
    for ($i = 0, $len = min(strlen($a), strlen($b)); $i < $len; $i++) {
      if ($a[$i] !== $b[$i]) $d++;
    }
    return $d;
  }

  // ── Index management ───────────────────────────────────────────────────────

  /**
   * Build full hash index for all active products and store in State.
   */
  public function buildIndex(): array {
    $index    = [];
    $products = $this->entityTypeManager
      ->getStorage('commerce_product')
      ->loadByProperties(['status' => 1]);

    foreach ($products as $product) {
      $entry = $this->hashProduct($product);
      if ($entry) {
        $index[(int) $product->id()] = $entry;
      }
    }
    $this->state->set(self::STATE_KEY, $index);
    return $index;
  }

  /** Add or update one product in the cached index. */
  public function rebuildForProduct(object $product): void {
    $index = $this->state->get(self::STATE_KEY, []);
    $entry = $this->hashProduct($product);
    if ($entry) {
      $index[(int) $product->id()] = $entry;
    }
    else {
      unset($index[(int) $product->id()]);
    }
    $this->state->set(self::STATE_KEY, $index);
  }

  /** Remove a product from the cached index. */
  public function removeFromIndex(int $productId): void {
    $index = $this->state->get(self::STATE_KEY, []);
    unset($index[$productId]);
    $this->state->set(self::STATE_KEY, $index);
  }

  /** Return cached index, building it if it does not exist yet. */
  public function getIndex(): array {
    $index = $this->state->get(self::STATE_KEY, []);
    return empty($index) ? $this->buildIndex() : $index;
  }

  // ── Search ─────────────────────────────────────────────────────────────────

  /**
   * Find products visually similar to $imagePath.
   *
   * @param string $imagePath  Absolute path to the uploaded/temp image.
   * @param int    $limit      Max results to return.
   * @return array  Each item: ['product_id', 'score' (0–100), 'distance'].
   */
  public function search(string $imagePath, int $limit = 12): array {
    $qHash  = $this->dHash($imagePath);
    $qColor = $this->avgRgb($imagePath);
    if (!$qHash) return [];

    $exact   = [];
    $similar = [];

    foreach ($this->getIndex() as $productId => $data) {
      $dist = $this->hammingDistance($qHash, $data['hash']);
      if ($dist > self::THRESHOLD_SIMILAR) continue;

      $hashScore  = 1 - ($dist / 64.0);
      $colorScore = 1.0;

      if ($qColor && !empty($data['color']) && count($qColor) === count($data['color'])) {
        $zoneCount = count($qColor) / 3;
        $totalDist = 0;
        for ($i = 0; $i < $zoneCount; $i++) {
          $totalDist += $this->hsvZoneDist($qColor, $data['color'], $i);
        }
        $colorScore = 1.0 - ($totalDist / $zoneCount);
      }

      // Colour weighted 60 % — stronger discriminator between similar shapes.
      $score = round(($hashScore * 0.4 + $colorScore * 0.6) * 100, 1);

      $entry = [
        'product_id' => (int) $productId,
        'score'      => $score,
        'distance'   => $dist,
      ];

      if ($dist <= self::THRESHOLD_EXACT && $colorScore >= self::COLOR_MIN_EXACT) {
        $entry['type'] = 'exact';
        $exact[] = $entry;
      }
      else {
        $entry['type'] = 'similar';
        $similar[] = $entry;
      }
    }

    usort($exact,   fn($a, $b) => $b['score'] <=> $a['score']);
    usort($similar, fn($a, $b) => $b['score'] <=> $a['score']);

    $exactLimit   = min(count($exact), (int) ceil($limit * 0.4));
    $similarLimit = $limit - $exactLimit;

    return array_merge(
      array_slice($exact,   0, $exactLimit),
      array_slice($similar, 0, $similarLimit)
    );
  }

  // ── Private helpers ────────────────────────────────────────────────────────

  /**
   * HSV-space distance between zone $idx of two flat RGB arrays.
   *
   * Weights: H 0.25 · S 0.25 · V 0.50
   * Value (brightness) is doubled because it cleanly separates navy/dark from
   * medium blue, and black from dark-grey — differences RGB distance misses.
   * Hue is suppressed when either zone is achromatic (S < 0.15) so that two
   * near-black zones are not penalised for having random hue jitter.
   * Returns 0.0 (identical) … ~1.0 (maximally different).
   */
  private function hsvZoneDist(array $rgb1, array $rgb2, int $idx): float {
    $o = $idx * 3;
    [$h1, $s1, $v1] = $this->rgbToHsv($rgb1[$o], $rgb1[$o + 1], $rgb1[$o + 2]);
    [$h2, $s2, $v2] = $this->rgbToHsv($rgb2[$o], $rgb2[$o + 1], $rgb2[$o + 2]);

    $hueDiff = (min($s1, $s2) < 0.15)
      ? 0.0
      : min(abs($h1 - $h2), 360 - abs($h1 - $h2)) / 180.0;

    return $hueDiff * 0.25 + abs($s1 - $s2) * 0.25 + abs($v1 - $v2) * 0.50;
  }

  /** Convert 0-255 RGB to [H(0-360), S(0-1), V(0-1)]. */
  private function rgbToHsv(float $r, float $g, float $b): array {
    $r /= 255; $g /= 255; $b /= 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $d   = $max - $min;
    $v   = $max;
    $s   = $max > 0 ? $d / $max : 0.0;
    if ($d == 0) {
      $h = 0.0;
    } elseif ($max === $r) {
      $h = 60 * fmod(($g - $b) / $d, 6);
    } elseif ($max === $g) {
      $h = 60 * (($b - $r) / $d + 2);
    } else {
      $h = 60 * (($r - $g) / $d + 4);
    }
    return [$h < 0 ? $h + 360 : $h, $s, $v];
  }

  private function hashProduct(object $product): ?array {
    if ($product->get('field_product_image')->isEmpty()) return NULL;

    $file = $product->get('field_product_image')->entity;
    if (!$file) return NULL;

    $realPath = $this->fileSystem->realpath($file->getFileUri());
    if (!$realPath || !file_exists($realPath)) return NULL;

    $hash = $this->dHash($realPath);
    if (!$hash) return NULL;

    return [
      'hash'    => $hash,
      'color'   => $this->avgRgb($realPath),
      'img_url' => $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri()),
    ];
  }
}
