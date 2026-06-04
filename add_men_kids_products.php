<?php
/**
 * Create subcategories + 10 products each for Men and Kids.
 *
 * Men  subcats: Jeans(12), T-Shirts, Shirts, Jackets, Shoes
 * Kids subcats: Tshirts(14), Jeans, Shorts, Hoodies, Shoes
 */

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

// ── Polyfill ──────────────────────────────────────────────────────────────────
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

// ── Image generator ───────────────────────────────────────────────────────────
function make_image(string $label, array $rgb, string $type, string $filename): ?File {
  $dir = 'public://products/';
  \Drupal::service('file_system')->prepareDirectory($dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
  $dest      = $dir . $filename;
  $full_path = \Drupal::service('file_system')->realpath($dir) . '/' . $filename;

  if (file_exists($full_path)) {
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $dest]);
    if ($files) return reset($files);
  }

  [$r,$g,$b] = $rgb;
  $img   = imagecreatetruecolor(400, 400);
  $bg    = imagecolorallocate($img, $r, $g, $b);
  $dark  = imagecolorallocate($img, max(0,$r-60), max(0,$g-60), max(0,$b-60));
  $light = imagecolorallocate($img, min(255,$r+70), min(255,$g+70), min(255,$b+70));
  $white = imagecolorallocate($img, 255, 255, 255);
  $grey  = imagecolorallocate($img, 200, 200, 200);

  imagefilledrectangle($img, 0, 0, 400, 400, $bg);
  imagefilledrectangle($img, 0, 260, 400, 400, $dark);

  switch ($type) {
    case 'tshirt':
      // Body
      imagefilledpolygon($img, [115,290,100,135,75,120,55,150,75,165,100,160,100,170,115,180], $white);
      imagefilledpolygon($img, [285,290,300,135,325,120,345,150,325,165,300,160,300,170,285,180], $white);
      imagefilledrectangle($img, 115, 170, 285, 290, $white);
      // collar
      imagefilledrectangle($img, 115, 130, 285, 175, $white);
      imagefilledellipse($img, 200, 135, 80, 40, $light);
      break;

    case 'shirt':
      imagefilledpolygon($img, [115,290,100,130,70,110,50,145,75,160,100,155,100,170,115,180], $white);
      imagefilledpolygon($img, [285,290,300,130,330,110,350,145,325,160,300,155,300,170,285,180], $white);
      imagefilledrectangle($img, 115, 165, 285, 290, $white);
      imagefilledrectangle($img, 115, 125, 285, 170, $white);
      // pointed collar
      imagefilledpolygon($img, [200,95,165,135,200,125], $white);
      imagefilledpolygon($img, [200,95,235,135,200,125], $white);
      // buttons
      for ($i=0;$i<4;$i++) imagefilledellipse($img, 200, 175+$i*28, 9, 9, $grey);
      break;

    case 'jeans':
      // waistband
      imagefilledrectangle($img, 120, 80, 280, 120, $light);
      // left leg
      imagefilledpolygon($img, [120,115,195,115,195,310,125,320], $white);
      // right leg
      imagefilledpolygon($img, [205,115,280,115,275,320,205,310], $white);
      // crotch curve approximation
      imagefilledrectangle($img, 190,115,210,155,$dark);
      // pockets
      imagefilledroundedrectangle($img, 125, 120, 180, 160, 5, $light);
      imagefilledroundedrectangle($img, 220, 120, 275, 160, 5, $light);
      break;

    case 'shorts':
      imagefilledrectangle($img, 120, 90, 280, 125, $light);
      imagefilledpolygon($img, [120,120,200,120,200,230,125,235], $white);
      imagefilledpolygon($img, [200,120,280,120,275,235,200,230], $white);
      imagefilledrectangle($img, 193,120,207,170,$dark);
      imagefilledroundedrectangle($img, 125, 128, 178, 165, 5, $light);
      imagefilledroundedrectangle($img, 222, 128, 275, 165, 5, $light);
      break;

    case 'jacket':
      // body
      imagefilledpolygon($img, [110,290,95,130,65,110,45,148,70,163,95,158,95,172,110,182], $white);
      imagefilledpolygon($img, [290,290,305,130,335,110,355,148,330,163,305,158,305,172,290,182], $white);
      imagefilledrectangle($img, 110, 172, 290, 290, $white);
      imagefilledrectangle($img, 110, 125, 290, 175, $white);
      // lapels
      imagefilledpolygon($img, [200,105,165,140,190,170,200,115], $light);
      imagefilledpolygon($img, [200,105,235,140,210,170,200,115], $light);
      // zip line
      imagefilledrectangle($img, 198, 165, 202, 285, $grey);
      // pockets
      imagefilledroundedrectangle($img, 118, 215, 170, 250, 4, $light);
      imagefilledroundedrectangle($img, 230, 215, 282, 250, 4, $light);
      break;

    case 'hoodie':
      imagefilledpolygon($img, [115,290,100,135,75,120,55,150,75,165,100,160,100,170,115,180], $white);
      imagefilledpolygon($img, [285,290,300,135,325,120,345,150,325,165,300,160,300,170,285,180], $white);
      imagefilledrectangle($img, 115, 170, 285, 290, $white);
      imagefilledrectangle($img, 115, 130, 285, 175, $white);
      // hood
      imagefilledarc($img, 200, 130, 140, 100, 190, 350, $white, IMG_ARC_PIE);
      imagefilledellipse($img, 200, 132, 70, 55, $light);
      // kangaroo pocket
      imagefilledroundedrectangle($img, 148, 238, 252, 280, 6, $light);
      break;

    case 'shoes':
      // sole
      imagefilledroundedrectangle($img, 70, 250, 330, 295, 18, $dark);
      // upper
      imagefilledpolygon($img, [80,255,80,190,130,145,200,130,270,145,320,190,320,255], $white);
      // toe cap
      imagefilledroundedrectangle($img, 75, 210, 195, 258, 20, $light);
      // tongue
      imagefilledrectangle($img, 178, 135, 222, 220, $light);
      // lace holes
      for ($i=0;$i<4;$i++) {
        imagefilledellipse($img, 185, 160+$i*15, 8, 8, $grey);
        imagefilledellipse($img, 215, 160+$i*15, 8, 8, $grey);
      }
      break;
  }

  // label
  $words = explode(' ', $label);
  $lines = []; $line = '';
  foreach ($words as $w) {
    if ($line !== '' && strlen("$line $w") > 22) { $lines[] = $line; $line = $w; }
    else { $line = $line === '' ? $w : "$line $w"; }
  }
  $lines[] = $line;
  $y = 312;
  foreach ($lines as $ln) {
    $tw = (int)(imagefontwidth(3) * strlen($ln));
    imagestring($img, 3, (400-$tw)/2, $y, $ln, $white);
    $y += 16;
  }

  imagepng($img, $full_path);
  imagedestroy($img);

  $file = File::create(['uri'=>$dest,'status'=>1,'uid'=>1]);
  $file->save();
  return $file;
}

// ── Ensure taxonomy term exists, return TID ───────────────────────────────────
function ensure_term(string $name, int $parent_tid): int {
  $existing = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
    ->loadByProperties(['name' => $name, 'vid' => 'product_category']);
  if ($existing) {
    $t = reset($existing);
    echo "  term exists TID {$t->id()}: $name\n";
    return (int)$t->id();
  }
  $term = Term::create([
    'name'   => $name,
    'vid'    => 'product_category',
    'parent' => [$parent_tid],
  ]);
  $term->save();
  echo "  created TID {$term->id()}: $name (parent=$parent_tid)\n";
  return (int)$term->id();
}

// ── Create product ────────────────────────────────────────────────────────────
function make_product(array $d): void {
  $exists = \Drupal::entityTypeManager()->getStorage('commerce_product')
    ->loadByProperties(['title' => $d['title']]);
  if ($exists) { echo "  SKIP: {$d['title']}\n"; return; }

  $store = \Drupal::entityTypeManager()->getStorage('commerce_store')->loadDefault();
  $variation = ProductVariation::create([
    'type' => 'default', 'sku' => $d['sku'],
    'price' => new \Drupal\commerce_price\Price((string)$d['price'], 'USD'),
    'status' => 1,
  ]);
  $variation->save();

  $pd = [
    'type' => 'default', 'title' => $d['title'], 'status' => 1, 'uid' => 1,
    'stores' => [$store], 'variations' => [$variation],
    'field_category' => [['target_id' => $d['tid']]],
    'field_tagline'  => $d['tagline'],
    'body' => ['value' => $d['body'], 'format' => 'basic_html', 'summary' => $d['tagline']],
  ];
  if (!empty($d['file'])) {
    $pd['field_product_image'] = ['target_id' => $d['file']->id(), 'alt' => $d['title']];
  }
  $p = Product::create($pd);
  $p->save();
  echo "  PID {$p->id()}: {$d['title']} \${$d['price']}\n";
}

// ═══════════════════════════════════════════════════════════════════════════════
// STEP 1 — ensure subcategory terms
// ═══════════════════════════════════════════════════════════════════════════════
echo "\n=== Creating subcategories ===\n";

$TID_MEN  = 11;
$TID_KIDS = 13;

$TID_MEN_JEANS   = 12; // already exists
$TID_MEN_TSHIRTS = ensure_term('T-Shirts', $TID_MEN);
$TID_MEN_SHIRTS  = ensure_term('Shirts',   $TID_MEN);
$TID_MEN_JACKETS = ensure_term('Jackets',  $TID_MEN);
$TID_MEN_SHOES   = ensure_term('Shoes',    $TID_MEN);

$TID_KIDS_TSHIRTS = 14; // already exists
$TID_KIDS_JEANS   = ensure_term('Kids Jeans',    $TID_KIDS);
$TID_KIDS_SHORTS  = ensure_term('Kids Shorts',   $TID_KIDS);
$TID_KIDS_HOODIES = ensure_term('Kids Hoodies',  $TID_KIDS);
$TID_KIDS_SHOES   = ensure_term('Kids Shoes',    $TID_KIDS);

// ═══════════════════════════════════════════════════════════════════════════════
// STEP 2 — product catalog
// ═══════════════════════════════════════════════════════════════════════════════

$idx = 29; // next image index after product-28.png

$catalog = [];

// ─────────────────────────────────────────────────────────────────────────────
// MEN'S JEANS (TID 12)
// ─────────────────────────────────────────────────────────────────────────────
$catalog[] = ['tid'=>$TID_MEN_JEANS,'type'=>'jeans','rgb'=>[40,70,130],'sku'=>'MJN-001','price'=>79.99,
  'title'=>"Men's Slim Fit Dark Wash Jeans",'tagline'=>'Sharp dark indigo slim fit for a polished everyday look.',
  'body'=>'<p>Crafted from 99% cotton with 1% elastane for comfort, these slim-fit jeans feature a dark indigo wash, five-pocket styling, zip fly with button closure, and a narrow leg opening. Ideal for smart-casual outfits.</p><ul><li>Material: 99% Cotton, 1% Elastane</li><li>Fit: Slim Fit</li><li>Waist sizes: 28–40 inches</li><li>Inseam: 30, 32, 34 inches</li><li>Wash: Dark Indigo</li><li>Care: Machine wash cold, inside out</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JEANS,'type'=>'jeans','rgb'=>[80,100,160],'sku'=>'MJN-002','price'=>69.99,
  'title'=>"Men's Relaxed Fit Straight Jeans",'tagline'=>'Classic straight cut with a relaxed seat and thigh.',
  'body'=>'<p>A timeless relaxed-fit jean in a mid-blue wash. Features a straight leg from hip to hem, five-pocket design, and a durable denim construction with a slight stretch for all-day comfort.</p><ul><li>Material: 98% Cotton, 2% Elastane</li><li>Fit: Relaxed Straight</li><li>Waist: 30–42 inches</li><li>Inseam: 30, 32, 34 inches</li><li>Wash: Mid Blue</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JEANS,'type'=>'jeans','rgb'=>[50,60,120],'sku'=>'MJN-003','price'=>74.99,
  'title'=>"Men's Skinny Stretch Jeans",'tagline'=>'Sculpting skinny fit with maximum 4-way stretch comfort.',
  'body'=>'<p>These skinny-fit jeans are engineered with a 4-way stretch denim that provides a body-sculpting silhouette without restricting movement. Zip fly, five pockets, and a mid-rise waist.</p><ul><li>Material: 92% Cotton, 6% Polyester, 2% Elastane</li><li>Fit: Skinny</li><li>Waist: 28–38 inches</li><li>Inseam: 30, 32, 34 inches</li><li>Wash: Dark Navy</li><li>Care: Machine wash cold</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JEANS,'type'=>'jeans','rgb'=>[70,90,150],'sku'=>'MJN-004','price'=>65.99,
  'title'=>"Men's Classic Bootcut Jeans",'tagline'=>'Slight flare at the hem — perfect over boots.',
  'body'=>'<p>A bootcut silhouette that sits slightly wider from the knee down, designed to be worn over ankle boots or work footwear. Comfortable mid-rise waistband and five-pocket styling.</p><ul><li>Material: 100% Cotton denim</li><li>Fit: Bootcut</li><li>Waist: 30–42 inches</li><li>Inseam: 30, 32, 34 inches</li><li>Wash: Medium Stonewash</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JEANS,'type'=>'jeans','rgb'=>[45,55,110],'sku'=>'MJN-005','price'=>84.99,
  'title'=>"Men's Distressed Ripped Jeans",'tagline'=>'Intentional rips and whiskers for street-ready edge.',
  'body'=>'<p>Bold distressed details including knee rips, thigh abrasions, and natural whisker fading give these slim-fit jeans a lived-in look fresh out of the bag. Zip fly and five-pocket construction.</p><ul><li>Material: 98% Cotton, 2% Elastane</li><li>Fit: Slim Fit</li><li>Waist: 28–38 inches</li><li>Inseam: 30, 32 inches</li><li>Wash: Light Distressed</li><li>Care: Machine wash cold, inside out</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JEANS,'type'=>'jeans','rgb'=>[60,85,145],'sku'=>'MJN-006','price'=>72.99,
  'title'=>"Men's Tapered Light Wash Jeans",'tagline'=>'Tapered leg in a clean light wash for effortless style.',
  'body'=>'<p>The tapered cut narrows gradually from thigh to ankle for a modern, on-trend silhouette. Finished in a clean light blue wash with minimal fading for a versatile look that pairs with trainers or loafers.</p><ul><li>Material: 98% Cotton, 2% Elastane</li><li>Fit: Tapered</li><li>Waist: 28–38 inches</li><li>Inseam: 30, 32 inches</li><li>Wash: Light Blue</li><li>Care: Machine wash cold</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JEANS,'type'=>'jeans','rgb'=>[35,50,95],'sku'=>'MJN-007','price'=>89.99,
  'title'=>"Men's Multi-Pocket Cargo Jeans",'tagline'=>'Utility cargo pockets fused onto rugged denim construction.',
  'body'=>'<p>Combining denim durability with cargo pocket practicality, these jeans feature two large thigh-mounted zip pockets in addition to the standard five-pocket layout. Straight leg fit and a secure waistband with belt loops.</p><ul><li>Material: 100% Cotton 12 oz denim</li><li>Fit: Straight</li><li>Waist: 30–40 inches</li><li>Inseam: 30, 32, 34 inches</li><li>Wash: Dark Charcoal</li><li>Pockets: 7 total (inc. 2 cargo zip)</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JEANS,'type'=>'jeans','rgb'=>[55,75,135],'sku'=>'MJN-008','price'=>77.99,
  'title'=>"Men's Jogger-Style Denim Jeans",'tagline'=>'Elasticated cuffed hem jogger cut in soft stretch denim.',
  'body'=>'<p>The hybrid between a tracksuit jogger and denim. Features a drawstring waist, elasticated cuffed ankles, and a soft stretch denim fabric that feels as comfortable as jersey but looks like jeans.</p><ul><li>Material: 78% Cotton, 18% Polyester, 4% Elastane</li><li>Fit: Jogger/Tapered</li><li>Waist: S–3XL (elasticated)</li><li>Inseam: 28, 30 inches</li><li>Wash: Mid Grey Denim</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JEANS,'type'=>'jeans','rgb'=>[30,45,90],'sku'=>'MJN-009','price'=>129.99,
  'title'=>"Men's Raw Selvedge Straight Jeans",'tagline'=>'Unwashed 13 oz Japanese selvedge denim — fades uniquely to you.',
  'body'=>'<p>Woven on vintage shuttle looms from 13 oz Japanese selvedge cotton, these raw denim jeans develop unique fade patterns with wear. Straight cut, single-stitch construction, and a natural indigo that will fade into a personalized pair over time.</p><ul><li>Material: 100% Japanese Selvedge Cotton, 13 oz</li><li>Fit: Straight</li><li>Waist: 28–36 inches</li><li>Inseam: 32, 34 inches</li><li>Wash: Raw Unwashed Indigo</li><li>Care: Soak in cold water; spot clean only for first 6 months</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JEANS,'type'=>'jeans','rgb'=>[65,80,140],'sku'=>'MJN-010','price'=>89.99,
  'title'=>"Men's Wide Leg Denim Jeans",'tagline'=>'Relaxed wide-leg cut inspired by 90s street style.',
  'body'=>'<p>A wide-leg silhouette with a high rise and generous fit through the hip and thigh, tapering only slightly at the hem. Inspired by 90s workwear denim, finished in a vintage indigo with subtle wash-down effects.</p><ul><li>Material: 100% Cotton</li><li>Fit: Wide Leg, High Rise</li><li>Waist: 28–38 inches</li><li>Inseam: 30, 32 inches</li><li>Wash: Vintage Indigo</li><li>Care: Machine wash cold</li></ul>'];

// ─────────────────────────────────────────────────────────────────────────────
// MEN'S T-SHIRTS
// ─────────────────────────────────────────────────────────────────────────────
$catalog[] = ['tid'=>$TID_MEN_TSHIRTS,'type'=>'tshirt','rgb'=>[220,220,220],'sku'=>'MTT-001','price'=>24.99,
  'title'=>"Men's Classic Crew Neck T-Shirt",'tagline'=>'Essential 180 gsm combed cotton crew neck — built to last.',
  'body'=>'<p>Cut from 180 gsm combed ring-spun cotton, this everyday crew neck tee offers a clean, versatile fit that sits slightly relaxed through the chest and waist. Reinforced shoulder seams and a tear-away label for all-day comfort.</p><ul><li>Material: 100% Combed Cotton, 180 gsm</li><li>Fit: Regular Fit</li><li>Sizes: XS–XXXL</li><li>Colors: White, Black, Navy, Grey Marl, Olive</li><li>Care: Machine wash 40°C, tumble dry low</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_TSHIRTS,'type'=>'tshirt','rgb'=>[30,30,30],'sku'=>'MTT-002','price'=>29.99,
  'title'=>"Men's Graphic Print T-Shirt",'tagline'=>'Bold front print on premium heavyweight cotton canvas.',
  'body'=>'<p>200 gsm heavyweight cotton with a screen-printed graphic on the chest. The oversized boxy cut and dropped shoulders give it a streetwear edge, while the thick fabric ensures the print doesn\'t crack or fade after washing.</p><ul><li>Material: 100% Cotton, 200 gsm</li><li>Fit: Oversized Boxy</li><li>Sizes: XS–XXL</li><li>Print: Water-based screen print</li><li>Colors: Black base, White base, Washed Grey</li><li>Care: Machine wash cold, inside out</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_TSHIRTS,'type'=>'tshirt','rgb'=>[60,100,160],'sku'=>'MTT-003','price'=>27.99,
  'title'=>"Men's V-Neck Slim T-Shirt",'tagline'=>'Slim-fit V-neck in soft-touch cotton modal blend.',
  'body'=>'<p>A deep V-neck design in a soft cotton-modal blend that drapes beautifully and resists shrinkage. The slim cut hugs the body without feeling tight, and the fabric is naturally anti-static and breathable.</p><ul><li>Material: 70% Cotton, 30% Modal</li><li>Fit: Slim Fit</li><li>Sizes: XS–XXL</li><li>Colors: White, Grey, Navy, Burgundy, Sage Green</li><li>Care: Machine wash 30°C, do not tumble dry</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_TSHIRTS,'type'=>'tshirt','rgb'=>[100,160,80],'sku'=>'MTT-004','price'=>22.99,
  'title'=>"Men's Pocket Chest T-Shirt",'tagline'=>'Clean minimal tee with a single utility chest pocket.',
  'body'=>'<p>A wardrobe staple with a chest pocket — simple, clean, and endlessly wearable. Made from a medium-weight slub cotton that gives the fabric a natural, textured appearance. Regular fit with a straight hem.</p><ul><li>Material: 100% Slub Cotton, 160 gsm</li><li>Fit: Regular Fit</li><li>Sizes: XS–XXXL</li><li>Colors: White, Ecru, Light Grey, Denim Blue, Terracotta</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_TSHIRTS,'type'=>'tshirt','rgb'=>[180,80,50],'sku'=>'MTT-005','price'=>34.99,
  'title'=>"Men's Long Sleeve T-Shirt",'tagline'=>'Midlayer-ready long sleeve tee in brushed cotton blend.',
  'body'=>'<p>A long-sleeve tee made from a brushed cotton-polyester blend that wears well solo in mild weather or as a base layer under a jacket. Features a crew neck, ribbed cuffs, and a slightly longer body for extra coverage.</p><ul><li>Material: 60% Cotton, 40% Polyester, brushed</li><li>Fit: Regular Fit</li><li>Sizes: XS–XXXL</li><li>Colors: Black, White, Charcoal, Navy, Forest Green</li><li>Care: Machine wash 40°C, tumble dry low</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_TSHIRTS,'type'=>'tshirt','rgb'=>[80,50,140],'sku'=>'MTT-006','price'=>31.99,
  'title'=>"Men's Henley Button T-Shirt",'tagline'=>'Three-button henley placket adds casual sophistication.',
  'body'=>'<p>A step up from the standard crew neck, the henley features a 3-button placket that can be worn open or closed. Made from a textured waffle-weave cotton that adds dimension and breathability.</p><ul><li>Material: 100% Waffle Weave Cotton</li><li>Fit: Regular Fit</li><li>Sizes: XS–XXXL</li><li>Colors: White, Stone, Camel, Slate Blue, Wine</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_TSHIRTS,'type'=>'tshirt','rgb'=>[160,160,200],'sku'=>'MTT-007','price'=>26.99,
  'title'=>"Men's Breton Stripe T-Shirt",'tagline'=>'Classic French navy and white Breton stripe on soft cotton.',
  'body'=>'<p>A nod to nautical heritage, this classic Breton stripe tee features navy and white horizontal stripes on a soft 170 gsm jersey. Crew neck, short sleeves, and a regular fit that works equally well on a boat or in a café.</p><ul><li>Material: 100% Cotton jersey, 170 gsm</li><li>Fit: Regular Fit</li><li>Sizes: XS–XXL</li><li>Colors: Navy/White, Red/White, Teal/White</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_TSHIRTS,'type'=>'tshirt','rgb'=>[60,160,140],'sku'=>'MTT-008','price'=>32.99,
  'title'=>"Men's Performance Dry-Fit T-Shirt",'tagline'=>'Moisture-wicking mesh keeps you cool during training.',
  'body'=>'<p>Engineered for performance, this training tee features a fast-wicking polyester mesh that moves sweat away from skin. Flatlock seams eliminate chafing, and the antimicrobial treatment keeps odours at bay through intense workouts.</p><ul><li>Material: 100% Recycled Polyester, moisture-wicking mesh</li><li>Fit: Athletic Fit</li><li>Sizes: XS–XXXL</li><li>UPF 30+ sun protection</li><li>Colors: Black, White, Royal Blue, Neon Green, Red</li><li>Care: Machine wash 30°C; do not use fabric softener</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_TSHIRTS,'type'=>'tshirt','rgb'=>[200,150,50],'sku'=>'MTT-009','price'=>36.99,
  'title'=>"Men's Tie-Dye Oversized T-Shirt",'tagline'=>'Hand-dyed tie-dye effect — every piece is one of a kind.',
  'body'=>'<p>Each shirt is individually dipped and tied, meaning no two are exactly alike. The oversized boxy cut and dropped shoulder placement make it ideal for laid-back weekend wear. Made from 100% organic cotton for a soft hand feel.</p><ul><li>Material: 100% GOTS Organic Cotton, 190 gsm</li><li>Fit: Oversized</li><li>Sizes: XS–XXL</li><li>Colors: Sunrise (yellow/orange), Ocean (blue/green), Sunset (pink/purple)</li><li>Care: Machine wash cold, separately first wash</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_TSHIRTS,'type'=>'tshirt','rgb'=>[50,80,50],'sku'=>'MTT-010','price'=>28.99,
  'title'=>"Men's Longline Extended T-Shirt",'tagline'=>'Extended hem longline cut with curved back for layering.',
  'body'=>'<p>A fashion-forward longline tee with a slightly longer curved hem at the back, designed for layering under open shirts and hoodies or wearing solo for a contemporary look. Drop shoulder and a clean, minimal aesthetic.</p><ul><li>Material: 95% Cotton, 5% Elastane jersey</li><li>Fit: Longline, Dropped Shoulder</li><li>Sizes: XS–XXL</li><li>Colors: Black, White, Khaki, Washed Grey</li><li>Care: Machine wash 30°C</li></ul>'];

// ─────────────────────────────────────────────────────────────────────────────
// MEN'S SHIRTS
// ─────────────────────────────────────────────────────────────────────────────
$catalog[] = ['tid'=>$TID_MEN_SHIRTS,'type'=>'shirt','rgb'=>[200,210,230],'sku'=>'MSR-001','price'=>59.99,
  'title'=>"Men's Classic Oxford Button Shirt",'tagline'=>'100% Oxford cotton — timeless smart casual staple.',
  'body'=>'<p>Woven from premium Oxford cotton, this shirt features a button-down collar, chest pocket, and a slim-fit cut. The structured fabric holds its shape all day whether tucked or untucked.</p><ul><li>Material: 100% Oxford Cotton</li><li>Fit: Slim Fit</li><li>Collar: Button-down</li><li>Sizes: XS–XXXL</li><li>Colors: White, Blue, Pink, Light Grey, Yellow</li><li>Care: Machine wash 40°C, light iron</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHIRTS,'type'=>'shirt','rgb'=>[220,200,150],'sku'=>'MSR-002','price'=>54.99,
  'title'=>"Men's Linen Summer Shirt",'tagline'=>'Breathable pure linen — stays cool in 35°C heat.',
  'body'=>'<p>Cut from 100% European linen, this relaxed-fit shirt is the ultimate warm-weather companion. The open weave allows airflow while the natural linen fibres absorb moisture. A mandarin collar and single-button cuffs keep the look minimal.</p><ul><li>Material: 100% Pure Linen</li><li>Fit: Relaxed</li><li>Collar: Mandarin/Band</li><li>Sizes: XS–XXXL</li><li>Colors: White, Natural Ecru, Sky Blue, Sage Green, Terracotta</li><li>Care: Machine wash 30°C; dry flat to avoid creasing</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHIRTS,'type'=>'shirt','rgb'=>[130,70,50],'sku'=>'MSR-003','price'=>49.99,
  'title'=>"Men's Flannel Check Shirt",'tagline'=>'Brushed flannel check — rugged warmth without bulk.',
  'body'=>'<p>Made from a soft brushed flannel in a classic tartan check, this shirt works as a casual top or an open overshirt. The medium-weight construction provides warmth in autumn and spring without overheating.</p><ul><li>Material: 100% Brushed Flannel Cotton</li><li>Fit: Regular Fit</li><li>Collar: Standard spread</li><li>Sizes: XS–XXXL</li><li>Colors: Red/Navy Check, Green/Brown Check, Blue/Grey Check</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHIRTS,'type'=>'shirt','rgb'=>[240,240,245],'sku'=>'MSR-004','price'=>64.99,
  'title'=>"Men's Slim Fit Formal Shirt",'tagline'=>'Crisp poplin formal shirt for boardroom and black-tie.',
  'body'=>'<p>Cut in a sharp slim fit from 100% combed cotton poplin, this formal shirt features a spread collar, French placket, and double-button barrel cuffs. The smooth poplin weave holds a clean press all day.</p><ul><li>Material: 100% Combed Cotton Poplin</li><li>Fit: Slim Fit</li><li>Collar: Spread</li><li>Cuffs: Double-button barrel</li><li>Sizes: 14.5–18 collar</li><li>Colors: White, Pale Blue, Pale Pink, Light Lilac</li><li>Care: Machine wash 40°C; press while damp</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHIRTS,'type'=>'shirt','rgb'=>[60,90,150],'sku'=>'MSR-005','price'=>69.99,
  'title'=>"Men's Denim Western Shirt",'tagline'=>'Lightweight denim shirt with snap buttons and yoke detail.',
  'body'=>'<p>A lightweight 7 oz denim shirt inspired by Western workwear styling. Snap-button closure, a back yoke, two chest flap pockets with snap buttons, and a classic pointed collar. Great as a shirt or worn open over a tee.</p><ul><li>Material: 100% Cotton, 7 oz denim</li><li>Fit: Regular Fit</li><li>Collar: Pointed Western</li><li>Sizes: XS–XXXL</li><li>Colors: Classic Indigo, Light Wash, Black Wash</li><li>Care: Machine wash cold, inside out</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHIRTS,'type'=>'shirt','rgb'=>[200,150,90],'sku'=>'MSR-006','price'=>74.99,
  'title'=>"Men's Hawaiian Camp Collar Shirt",'tagline'=>'Resort-ready camp collar in bold tropical print.',
  'body'=>'<p>A holiday wardrobe essential. The camp collar sits flat and open, and the shirt is woven from a lightweight rayon-cotton blend printed with a vibrant tropical motif. Box pleat on the back for ease of movement.</p><ul><li>Material: 70% Viscose, 30% Cotton</li><li>Fit: Relaxed</li><li>Collar: Camp/Revere</li><li>Sizes: XS–XXXL</li><li>Colors: Tropical Floral, Hibiscus Red, Island Blue</li><li>Care: Machine wash 30°C, gentle cycle</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHIRTS,'type'=>'shirt','rgb'=>[150,170,190],'sku'=>'MSR-007','price'=>44.99,
  'title'=>"Men's Chambray Work Shirt",'tagline'=>'Lightweight chambray — versatile between work and weekend.',
  'body'=>'<p>Chambray is the lighter, less structured cousin of denim and is ideal for year-round wear. This shirt features a standard collar, single chest pocket, and a regular fit that sits relaxed without being baggy.</p><ul><li>Material: 100% Cotton Chambray</li><li>Fit: Regular Fit</li><li>Collar: Classic Point</li><li>Sizes: XS–XXXL</li><li>Colors: Blue, Mid Blue, Slate Grey, Khaki</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHIRTS,'type'=>'shirt','rgb'=>[90,50,120],'sku'=>'MSR-008','price'=>79.99,
  'title'=>"Men's Dobby Texture Formal Shirt",'tagline'=>'Self-stripe dobby weave adds quiet texture to a classic formal.',
  'body'=>'<p>A subtle dobby weave creates a tonal self-stripe visible only in direct light, elevating this formal shirt above plain poplin alternatives. Semi-spread collar, French front placket, and convertible cuffs suitable for cufflinks.</p><ul><li>Material: 100% Cotton Dobby</li><li>Fit: Slim Fit</li><li>Collar: Semi-spread</li><li>Cuffs: Convertible (button or cufflinks)</li><li>Sizes: 14.5–18 collar</li><li>Colors: White, Pale Blue, Silver Grey</li><li>Care: Machine wash 40°C; press with steam</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHIRTS,'type'=>'shirt','rgb'=>[60,130,90],'sku'=>'MSR-009','price'=>52.99,
  'title'=>"Men's Mandarin Collar Linen Shirt",'tagline'=>'Minimal band collar linen for a clean, modern aesthetic.',
  'body'=>'<p>A refined take on casual shirting: the collarless mandarin band keeps things clean and modern, pairing well with tailored trousers or chinos. Made from a premium linen-cotton blend for comfort and breathability.</p><ul><li>Material: 55% Linen, 45% Cotton</li><li>Fit: Regular Fit</li><li>Collar: Mandarin/Band (no collar)</li><li>Sizes: XS–XXXL</li><li>Colors: White, Stone, Navy, Sage, Rust</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHIRTS,'type'=>'shirt','rgb'=>[170,100,60],'sku'=>'MSR-010','price'=>84.99,
  'title'=>"Men's Twill Overshirt Jacket",'tagline'=>'Heavy twill shirt worn as a layering jacket over tees.',
  'body'=>'<p>Blurring the line between shirt and jacket, this heavy 280 gsm twill overshirt features a longer body, relaxed boxy fit, chest and hip pockets, and a single-button front. Ideal as a casual outer layer in spring and autumn.</p><ul><li>Material: 100% Cotton Twill, 280 gsm</li><li>Fit: Relaxed / Overshirt</li><li>Collar: Classic Point</li><li>Pockets: 2 chest flap + 2 hip</li><li>Sizes: XS–XXXL</li><li>Colors: Khaki, Navy, Olive, Camel</li><li>Care: Machine wash 40°C</li></ul>'];

// ─────────────────────────────────────────────────────────────────────────────
// MEN'S JACKETS
// ─────────────────────────────────────────────────────────────────────────────
$catalog[] = ['tid'=>$TID_MEN_JACKETS,'type'=>'jacket','rgb'=>[30,30,30],'sku'=>'MJK-001','price'=>199.99,
  'title'=>"Men's Genuine Leather Biker Jacket",'tagline'=>'Full-grain cowhide biker jacket — gets better with age.',
  'body'=>'<p>Crafted from full-grain cowhide, this asymmetric zip biker jacket develops a rich patina with wear. Quilted lining, belted waist, four exterior zip pockets, and two interior pockets.</p><ul><li>Material: Full-grain cowhide leather</li><li>Lining: Quilted polyester</li><li>Fit: Regular Fit</li><li>Sizes: XS–XXXL</li><li>Colors: Classic Black, Dark Brown</li><li>Care: Professional leather clean; condition with leather balm</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JACKETS,'type'=>'jacket','rgb'=>[60,90,130],'sku'=>'MJK-002','price'=>149.99,
  'title'=>"Men's Quilted Puffer Jacket",'tagline'=>'700-fill duck down puffer — warm to -15°C.',
  'body'=>'<p>Insulated with 700-fill-power duck down, this lightweight puffer compresses into its own pocket pouch for easy carrying. A durable water-repellent outer shell and baffled construction traps warmth without bulk.</p><ul><li>Fill: 700-fp Duck Down, 80/20 blend</li><li>Shell: 100% Recycled Nylon, DWR finish</li><li>Fit: Regular Fit</li><li>Sizes: XS–XXXL</li><li>Colors: Navy, Black, Khaki, Burgundy, Slate Grey</li><li>Packable: Yes — into chest pocket</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JACKETS,'type'=>'jacket','rgb'=>[170,120,50],'sku'=>'MJK-003','price'=>89.99,
  'title'=>"Men's Lightweight Bomber Jacket",'tagline'=>'MA-1 inspired satin bomber in a slim athletic cut.',
  'body'=>'<p>A fashion-forward satin bomber in the classic MA-1 silhouette. Features ribbed collar, cuffs, and hem, two side pockets, one interior pocket, and a clean zip-front closure. The glossy satin shell adds a premium look at an accessible price.</p><ul><li>Material: 100% Polyester satin shell, satin lining</li><li>Fit: Slim Fit</li><li>Sizes: XS–XXL</li><li>Colors: Olive, Black, Cadet Blue, Burgundy</li><li>Care: Machine wash 30°C, gentle</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JACKETS,'type'=>'jacket','rgb'=>[50,50,80],'sku'=>'MJK-004','price'=>299.99,
  'title'=>"Men's Wool-Cashmere Overcoat",'tagline'=>'Classic single-breasted overcoat in Italian wool-cashmere.',
  'body'=>'<p>A timeless long overcoat cut from an Italian wool-cashmere blend that drapes beautifully. Single-breasted with notch lapels, welt pockets, and a fully lined interior. An investment piece that elevates any outfit.</p><ul><li>Material: 80% Wool, 20% Cashmere</li><li>Lining: 100% Viscose</li><li>Fit: Slim Fit</li><li>Length: Knee-length</li><li>Sizes: XS–XXXL</li><li>Colors: Camel, Charcoal, Midnight Navy, Black</li><li>Care: Dry clean only</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JACKETS,'type'=>'jacket','rgb'=>[80,70,50],'sku'=>'MJK-005','price'=>169.99,
  'title'=>"Men's Waxed Cotton Field Jacket",'tagline'=>'Re-waxable cotton field jacket — weatherproof heritage styling.',
  'body'=>'<p>Modelled on the iconic British field jacket, this coat is made from re-waxable cotton canvas that repels wind and rain. Features a corduroy collar, four large patch pockets, and a game pocket on the inside back.</p><ul><li>Material: 100% Waxed Cotton Canvas</li><li>Lining: Tartan cotton</li><li>Fit: Regular</li><li>Sizes: XS–XXXL</li><li>Colors: Olive Green, Dark Navy, Dark Brown</li><li>Care: Re-wax annually; do not machine wash</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JACKETS,'type'=>'jacket','rgb'=>[100,140,180],'sku'=>'MJK-006','price'=>74.99,
  'title'=>"Men's Lightweight Windbreaker",'tagline'=>'Packable ripstop nylon windbreaker — essential for layering.',
  'body'=>'<p>A packable ripstop nylon windbreaker with a DWR coating that shields against light rain and wind. Stuff it into the interior chest pocket for a palm-sized carry. Elasticated cuffs and hem, and two zip pockets.</p><ul><li>Material: 100% Ripstop Nylon, DWR coated</li><li>Fit: Regular</li><li>Sizes: XS–XXXL</li><li>Colors: Navy, Black, Khaki, Electric Blue</li><li>Packable: Into chest pocket</li><li>Care: Machine wash 30°C; re-apply DWR after washing</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JACKETS,'type'=>'jacket','rgb'=>[70,100,70],'sku'=>'MJK-007','price'=>119.99,
  'title'=>"Men's Softshell Fleece Jacket",'tagline'=>'Windproof softshell with cosy fleece interior for active use.',
  'body'=>'<p>The ultimate active mid-layer: a windproof and water-resistant outer softshell bonded to a warm fleece interior. Fitted cut designed for movement, with two zip hand pockets and a chin guard on the zip.</p><ul><li>Material: Outer: Polyester softshell; Inner: 200g fleece</li><li>Wind/Water: Windproof, water-resistant</li><li>Fit: Athletic Fit</li><li>Sizes: XS–XXXL</li><li>Colors: Forest Green, Black, Charcoal, Navy</li><li>Care: Machine wash 30°C; do not tumble dry</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JACKETS,'type'=>'jacket','rgb'=>[50,50,130],'sku'=>'MJK-008','price'=>109.99,
  'title'=>"Men's Varsity College Jacket",'tagline'=>'American varsity letterman jacket in wool and leather sleeves.',
  'body'=>'<p>The classic varsity jacket reimagined with a premium wool body and genuine leather sleeves. Snap-button front, ribbed collar, cuffs, and waistband. Interior chest pocket and sleeve pocket.</p><ul><li>Body: 80% Wool, 20% Polyester</li><li>Sleeves: Genuine bovine leather</li><li>Fit: Regular</li><li>Sizes: XS–XXXL</li><li>Colors: Navy/White, Maroon/Grey, Black/Black</li><li>Care: Dry clean only</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JACKETS,'type'=>'jacket','rgb'=>[60,55,45],'sku'=>'MJK-009','price'=>134.99,
  'title'=>"Men's Military Cargo Jacket",'tagline'=>'M65-inspired field jacket with detachable liner.',
  'body'=>'<p>Inspired by the US Army M-65 field jacket, this coat features a poplin outer shell, detachable quilted liner, four flap pockets, and an adjustable storm hood that folds into the collar. Built tough, looks great.</p><ul><li>Shell: 65% Polyester, 35% Cotton poplin</li><li>Liner: Removable quilted polyester</li><li>Fit: Regular</li><li>Sizes: XS–XXXL</li><li>Colors: Olive Drab, Khaki, Black, Field Grey</li><li>Care: Machine wash 40°C; remove liner before washing</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_JACKETS,'type'=>'jacket','rgb'=>[55,80,140],'sku'=>'MJK-010','price'=>94.99,
  'title'=>"Men's Denim Trucker Jacket",'tagline'=>'Iconic three-button trucker jacket in 12 oz rigid denim.',
  'body'=>'<p>The original trucker silhouette: structured chest darts, two flap chest pockets, adjustable button tabs at the waist, and a rigid 12 oz denim that softens to your shape over time. A forever piece.</p><ul><li>Material: 100% Cotton Denim, 12 oz</li><li>Fit: Regular</li><li>Sizes: XS–XXXL</li><li>Colors: Rigid Indigo, Mid Wash, Black Rigid</li><li>Care: Machine wash cold, inside out; do not tumble dry</li></ul>'];

// ─────────────────────────────────────────────────────────────────────────────
// MEN'S SHOES
// ─────────────────────────────────────────────────────────────────────────────
$catalog[] = ['tid'=>$TID_MEN_SHOES,'type'=>'shoes','rgb'=>[240,240,240],'sku'=>'MSE-001','price'=>89.99,
  'title'=>"Men's Clean White Leather Sneakers",'tagline'=>'Minimalist white leather cupsole — timeless off-duty icon.',
  'body'=>'<p>Full-grain leather uppers sit atop a clean cupsole, delivering a minimal silhouette that goes with virtually anything. Cushioned OrthoLite insole, perforated toe box for breathability, and a leather lining for comfort.</p><ul><li>Upper: Full-grain leather</li><li>Sole: Vulcanised rubber cupsole</li><li>Sizes: UK 6–13</li><li>Colors: Triple White, White/Tan, White/Navy</li><li>Insole: Removable OrthoLite</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHOES,'type'=>'shoes','rgb'=>[80,55,35],'sku'=>'MSE-002','price'=>129.99,
  'title'=>"Men's Suede Chelsea Boots",'tagline'=>'Pull-on suede Chelsea with a block heel and elastic gussets.',
  'body'=>'<p>The Chelsea boot silhouette in premium split suede. Twin elastic gussets allow easy on-and-off wearing, and a 3 cm block heel adds a subtle lift. Leather sock lining and a durable rubber outsole.</p><ul><li>Upper: Split suede leather</li><li>Lining: Leather sock lining</li><li>Heel: 3 cm block rubber heel</li><li>Sizes: UK 6–13</li><li>Colors: Tan, Dark Brown, Slate Grey, Black</li><li>Care: Suede protector spray recommended</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHOES,'type'=>'shoes','rgb'=>[50,180,120],'sku'=>'MSE-003','price'=>109.99,
  'title'=>"Men's Road Running Trainers",'tagline'=>'Responsive 3D-printed midsole for long-distance road running.',
  'body'=>'<p>Built for daily road running, these trainers feature a knitted mesh upper for breathability, a dual-density foam midsole with a carbon-infused plate for energy return, and a high-grip rubber outsole patterned for road and treadmill.</p><ul><li>Upper: Engineered knit mesh</li><li>Midsole: Dual-density EVA + carbon plate</li><li>Outsole: Continental rubber</li><li>Drop: 10 mm</li><li>Weight: 270 g (UK 9)</li><li>Sizes: UK 6–14</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHOES,'type'=>'shoes','rgb'=>[30,30,30],'sku'=>'MSE-004','price'=>159.99,
  'title'=>"Men's Oxford Dress Shoes",'tagline'=>'Hand-stitched Goodyear welted Oxford in burnished calfskin.',
  'body'=>'<p>The pinnacle of classic footwear: a fully-closed lace Oxford with a Goodyear welt construction for superior durability and resolability. Burnished calfskin uppers, leather insole, and a leather-and-rubber combination outsole.</p><ul><li>Upper: Full-grain calfskin leather</li><li>Construction: Goodyear Welt</li><li>Sole: Leather + rubber combination</li><li>Sizes: UK 6–13 (half sizes available)</li><li>Colors: Black, Dark Tan, Mid Brown</li><li>Care: Polish and condition regularly</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHOES,'type'=>'shoes','rgb'=>[50,50,50],'sku'=>'MSE-005','price'=>74.99,
  'title'=>"Men's Canvas High-Top Sneakers",'tagline'=>'Vulcanised canvas high-top — retro American basketball icon.',
  'body'=>'<p>A high-top canvas sneaker inspired by 1970s American basketball courts. Thick vulcanised rubber cupsole, die-cut ankle patch, metal eyelets, and a cushioned insole. Canvas upper in a range of seasonal colour-ways.</p><ul><li>Upper: 100% Cotton Canvas</li><li>Sole: Vulcanised natural rubber</li><li>Sizes: UK 4–13</li><li>Colors: Black, White, Red, Olive, Burgundy</li><li>Care: Spot clean with damp cloth</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHOES,'type'=>'shoes','rgb'=>[160,120,70],'sku'=>'MSE-006','price'=>94.99,
  'title'=>"Men's Leather Penny Loafers",'tagline'=>'Slip-on penny loafers in smooth calfskin — effortlessly polished.',
  'body'=>'<p>A classic American Ivy League slip-on with the signature penny strap across the vamp. Smooth calfskin upper, genuine leather lining, and a flexible leather-and-rubber sole. Versatile enough for business-casual or weekend wear.</p><ul><li>Upper: Smooth calfskin leather</li><li>Lining: Leather</li><li>Sole: Leather/rubber combination</li><li>Sizes: UK 6–13</li><li>Colors: Tan, Dark Brown, Black, Burgundy</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHOES,'type'=>'shoes','rgb'=>[100,80,50],'sku'=>'MSE-007','price'=>139.99,
  'title'=>"Men's Waterproof Hiking Boots",'tagline'=>'GORE-TEX membrane, Vibram outsole — built for any trail.',
  'body'=>'<p>Serious trail footwear: a full-grain leather and textile upper with a GORE-TEX Extended Comfort insert for guaranteed waterproofing. Vibram MegaGrip outsole delivers confidence on wet rock and mud, while a torsional shank adds stability on uneven ground.</p><ul><li>Upper: Full-grain leather + textile</li><li>Membrane: GORE-TEX Extended Comfort</li><li>Outsole: Vibram MegaGrip</li><li>Sizes: UK 6–14</li><li>Colors: Brown/Green, Black/Grey, Tan/Orange</li><li>Care: Clean with damp brush; re-proof with wax</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHOES,'type'=>'shoes','rgb'=>[200,170,120],'sku'=>'MSE-008','price'=>64.99,
  'title'=>"Men's Jute Canvas Espadrilles",'tagline'=>'Traditional hand-stitched jute rope sole for summer ease.',
  'body'=>'<p>A Spanish classic: a canvas upper hand-stitched onto a layered jute rope sole. Slip-on design, elasticated vamp, and a ribbon trim at the collar. Lightweight and perfect for warm days by the sea or in the city.</p><ul><li>Upper: Canvas</li><li>Sole: Natural jute rope</li><li>Sizes: UK 6–13</li><li>Colors: Navy, White, Tan, Ecru/Stripe, Red</li><li>Care: Do not submerge in water; air dry if damp</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHOES,'type'=>'shoes','rgb'=>[60,40,30],'sku'=>'MSE-009','price'=>149.99,
  'title'=>"Men's Wingtip Derby Brogues",'tagline'=>'Full-brogue Derbys on a Dainite rubber sole — smart and practical.',
  'body'=>'<p>The full brogue (wingtip) pattern punched along every seam gives these Derby shoes their classic character. Open lacing for an easier fit, leather uppers, a fully leather lining, and a Dainite studded rubber sole that handles city streets and cobblestones alike.</p><ul><li>Upper: Polished grain leather</li><li>Lining: Full leather</li><li>Sole: Dainite studded rubber</li><li>Sizes: UK 6–13</li><li>Colors: Tan, Oxford Brown, Black</li><li>Care: Polish regularly; use shoe trees when stored</li></ul>'];

$catalog[] = ['tid'=>$TID_MEN_SHOES,'type'=>'shoes','rgb'=>[40,40,40],'sku'=>'MSE-010','price'=>99.99,
  'title'=>"Men's Chunky Platform Sneakers",'tagline'=>'50 mm chunky sole platform sneaker for bold street presence.',
  'body'=>'<p>An oversized 50 mm platform cupsole gives maximum visual impact to this thick-sole sneaker. Padded tongue and collar, a re-enforced toe cap, and a multi-panel upper in premium synthetic leather combine retro aesthetics with modern comfort.</p><ul><li>Upper: Synthetic leather + textile panels</li><li>Platform height: 50 mm</li><li>Sole: Chunky rubber cupsole</li><li>Sizes: UK 6–13</li><li>Colors: Triple White, Triple Black, White/Brown</li></ul>'];

// ─────────────────────────────────────────────────────────────────────────────
// KIDS' T-SHIRTS (TID 14)
// ─────────────────────────────────────────────────────────────────────────────
$catalog[] = ['tid'=>$TID_KIDS_TSHIRTS,'type'=>'tshirt','rgb'=>[255,120,40],'sku'=>'KTT-001','price'=>14.99,
  'title'=>"Kids' Dinosaur Print T-Shirt",'tagline'=>'Roarsome all-over dino print in GOTS organic cotton.',
  'body'=>'<p>Let their imagination roam free with this all-over dinosaur print T-shirt in GOTS-certified organic cotton. Soft jersey is gentle on sensitive skin; reinforced shoulder seams handle the roughest play.</p><ul><li>Material: 100% GOTS Organic Cotton, 160 gsm</li><li>Fit: Regular</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8 yrs</li><li>Colors: Orange, Green, Blue</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_TSHIRTS,'type'=>'tshirt','rgb'=>[50,80,200],'sku'=>'KTT-002','price'=>15.99,
  'title'=>"Kids' Superhero Graphic Tee",'tagline'=>'Bold superhero chest print to fuel little imaginations.',
  'body'=>'<p>A bold chest graphic of a superhero cape design. Made from a durable 180 gsm cotton jersey with a plastisol print that won\'t crack after repeated washing. Crew neck and a straight hem.</p><ul><li>Material: 100% Cotton, 180 gsm</li><li>Fit: Regular</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9 yrs</li><li>Colors: Royal Blue, Red, Yellow</li><li>Care: Machine wash 40°C, do not tumble dry print</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_TSHIRTS,'type'=>'tshirt','rgb'=>[240,100,150],'sku'=>'KTT-003','price'=>13.99,
  'title'=>"Kids' Rainbow Stripe T-Shirt",'tagline'=>'Cheerful horizontal rainbow stripes in soft organic jersey.',
  'body'=>'<p>A bright rainbow-stripe tee knitted in reactive-dyed organic cotton for long-lasting colour. Crew neck, short set-in sleeves, and a slight drop at the back hem for easy tucking or untucking.</p><ul><li>Material: 100% Organic Cotton</li><li>Fit: Regular</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8 yrs</li><li>Colors: Rainbow Multistripe, Pastel Multistripe</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_TSHIRTS,'type'=>'tshirt','rgb'=>[20,20,60],'sku'=>'KTT-004','price'=>16.99,
  'title'=>"Kids' Space Explorer T-Shirt",'tagline'=>'Glow-in-the-dark galaxy print for little astronauts.',
  'body'=>'<p>A space-themed tee with a front print of planets, rockets, and stars using glow-in-the-dark ink. Made from soft ring-spun cotton jersey. The glow pigment is non-toxic and wash-safe.</p><ul><li>Material: 100% Ring-spun Cotton, 170 gsm</li><li>Fit: Regular</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Midnight Blue, Charcoal Black</li><li>Care: Machine wash 30°C, inside out</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_TSHIRTS,'type'=>'tshirt','rgb'=>[80,180,80],'sku'=>'KTT-005','price'=>13.99,
  'title'=>"Kids' Animal Print T-Shirt",'tagline'=>'All-over animal print in cheerful organic cotton jersey.',
  'body'=>'<p>An all-over print featuring illustrations of jungle animals including lions, elephants, and giraffes. Printed using reactive dyes on GOTS-certified organic cotton for safe, vibrant colour. Crew neck, short sleeves.</p><ul><li>Material: 100% GOTS Organic Cotton</li><li>Fit: Regular</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8 yrs</li><li>Colors: Jungle Green, Safari Yellow, Ocean Blue</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_TSHIRTS,'type'=>'tshirt','rgb'=>[200,80,180],'sku'=>'KTT-006','price'=>17.99,
  'title'=>"Kids' Tie-Dye Effect T-Shirt",'tagline'=>'Every shirt is hand-dyed — no two are exactly the same.',
  'body'=>'<p>Using a traditional tie-dye process on 100% organic cotton, each shirt emerges with a unique swirling pattern. The hand-knotting and dipping process ensures every piece is genuinely one-of-a-kind.</p><ul><li>Material: 100% Organic Cotton, 180 gsm</li><li>Fit: Regular / Relaxed</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Sunrise, Ocean, Berry</li><li>Care: Machine wash cold; colours may bleed first wash</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_TSHIRTS,'type'=>'tshirt','rgb'=>[255,200,30],'sku'=>'KTT-007','price'=>14.99,
  'title'=>"Kids' Sports Number T-Shirt",'tagline'=>'Athletic-style number print tee in moisture-wicking mesh.',
  'body'=>'<p>An athletic jersey-style tee with a large number chest print. Made from lightweight, moisture-wicking polyester mesh. Perfect for PE, football, or simply playing in the garden.</p><ul><li>Material: 100% Polyester, moisture-wicking</li><li>Fit: Regular</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: White/Navy, Red/White, Black/Yellow</li><li>Care: Machine wash 30°C; do not use fabric softener</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_TSHIRTS,'type'=>'tshirt','rgb'=>[170,170,200],'sku'=>'KTT-008','price'=>12.99,
  'title'=>"Kids' Plain Essential T-Shirt",'tagline'=>'Versatile solid-colour tee — perfect under everything.',
  'body'=>'<p>The building block of any child\'s wardrobe: a plain 160 gsm cotton crew neck tee in a rainbow of solid colours. Regular fit with set-in short sleeves and a clean hem. Available in a multi-pack option.</p><ul><li>Material: 100% Cotton, 160 gsm</li><li>Fit: Regular</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: White, Black, Navy, Red, Yellow, Pink, Mint, Grey</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_TSHIRTS,'type'=>'tshirt','rgb'=>[100,50,150],'sku'=>'KTT-009','price'=>16.99,
  'title'=>"Kids' Long Sleeve T-Shirt",'tagline'=>'Soft long sleeve tee doubles as a base layer or solo top.',
  'body'=>'<p>A soft long-sleeve tee that works beautifully solo or as a thermal base layer under a hoodie. Made from a fine-jersey cotton-modal blend with flatlock seams to eliminate any itching next to skin.</p><ul><li>Material: 70% Cotton, 30% Modal</li><li>Fit: Regular</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: White, Navy, Slate Blue, Dusty Pink, Forest Green</li><li>Care: Machine wash 30°C, lay flat to dry</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_TSHIRTS,'type'=>'tshirt','rgb'=>[60,140,200],'sku'=>'KTT-010','price'=>15.99,
  'title'=>"Kids' Pocket T-Shirt",'tagline'=>'Simple chest pocket adds a grown-up detail to a kids\' classic.',
  'body'=>'<p>A miniaturised version of the adult utility tee: a chest pocket, slub cotton fabric with natural texture, and a slightly relaxed fit with a curved hem. Available in earthy and classic tones.</p><ul><li>Material: 100% Slub Cotton, 160 gsm</li><li>Fit: Regular / Relaxed</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Ecru, Olive, Dusty Blue, Terracotta, White</li><li>Care: Machine wash 40°C</li></ul>'];

// ─────────────────────────────────────────────────────────────────────────────
// KIDS' JEANS
// ─────────────────────────────────────────────────────────────────────────────
$catalog[] = ['tid'=>$TID_KIDS_JEANS,'type'=>'jeans','rgb'=>[50,80,160],'sku'=>'KJN-001','price'=>29.99,
  'title'=>"Kids' Slim Fit Dark Wash Jeans",'tagline'=>'Mini-me slim fit in a smart dark indigo wash.',
  'body'=>'<p>A slim-fit jean for kids with a stretch denim construction to allow full freedom of movement at play. Dark indigo wash, five-pocket styling, and a sturdy zip fly with button closure.</p><ul><li>Material: 98% Cotton, 2% Elastane</li><li>Fit: Slim</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Waist: Adjustable inner elastic</li><li>Care: Machine wash cold</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_JEANS,'type'=>'jeans','rgb'=>[80,110,180],'sku'=>'KJN-002','price'=>27.99,
  'title'=>"Kids' Elasticated Waist Jeans",'tagline'=>'No-fuss elasticated waist for easy dressing.',
  'body'=>'<p>A relaxed-fit jean with a fully elasticated waistband — no buttons, no zips, just pull-on ease. Mid-blue wash, five-pocket design, and a durable cotton denim construction for energetic days.</p><ul><li>Material: 100% Cotton denim</li><li>Fit: Relaxed</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8 yrs</li><li>Waist: Fully elasticated</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_JEANS,'type'=>'jeans','rgb'=>[60,90,150],'sku'=>'KJN-003','price'=>31.99,
  'title'=>"Kids' Ripped Knee Jeans",'tagline'=>'Cool distressed rips at the knee for that street-ready look.',
  'body'=>'<p>Fashion-forward distressed jeans with intentional rips at the knee. Slim fit, stretch denim, internal adjustable waist, and five-pocket styling. Looks cool while staying comfy.</p><ul><li>Material: 98% Cotton, 2% Elastane</li><li>Fit: Slim</li><li>Ages: 5–6, 6–7, 7–8, 8–9, 9–10, 10–11 yrs</li><li>Waist: Internal adjustment button</li><li>Care: Machine wash cold, inside out</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_JEANS,'type'=>'jeans','rgb'=>[40,60,120],'sku'=>'KJN-004','price'=>26.99,
  'title'=>"Kids' Skinny Stretch Jeans",'tagline'=>'Ultra-stretch skinny fit for active, on-the-go kids.',
  'body'=>'<p>Made from a 4-way stretch denim, these skinny jeans move with your child during running, jumping, and climbing. High cotton content keeps them breathable; the slim silhouette tucks neatly into boots or wears great with trainers.</p><ul><li>Material: 90% Cotton, 8% Polyester, 2% Elastane</li><li>Fit: Skinny</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Care: Machine wash cold</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_JEANS,'type'=>'jeans','rgb'=>[70,95,160],'sku'=>'KJN-005','price'=>32.99,
  'title'=>"Kids' Jogger Denim Jeans",'tagline'=>'Denim look, jogger feel — elasticated cuffs and drawstring.',
  'body'=>'<p>Combines the aesthetic of jeans with the comfort of tracksuit bottoms. Soft stretch denim, drawstring waist, and elasticated cuffed ankles give these jeans a hybrid appeal. Ideal for both school and casual days.</p><ul><li>Material: 78% Cotton, 18% Polyester, 4% Elastane</li><li>Fit: Jogger Tapered</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_JEANS,'type'=>'jeans','rgb'=>[90,115,170],'sku'=>'KJN-006','price'=>28.99,
  'title'=>"Kids' Light Wash Straight Jeans",'tagline'=>'Clean light blue wash with a comfortable straight cut.',
  'body'=>'<p>A classic straight-leg jean in a clean light wash with subtle fading. Versatile enough for school days and weekend adventures. Five-pocket design and a hidden adjustable waist.</p><ul><li>Material: 100% Cotton</li><li>Fit: Straight</li><li>Ages: 4–5, 5–6, 6–7, 7–8, 8–9, 9–10, 10–11 yrs</li><li>Waist: Hidden internal adjustment</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_JEANS,'type'=>'jeans','rgb'=>[50,75,135],'sku'=>'KJN-007','price'=>34.99,
  'title'=>"Kids' Cargo Denim Jeans",'tagline'=>'Denim with utility cargo pockets for extra adventure storage.',
  'body'=>'<p>Straight-leg jeans with two large zip-fastened cargo pockets on the thighs — great for kids who always need more pocket space. Durable 12 oz cotton denim, five-pocket construction, and a fly-button waist.</p><ul><li>Material: 100% Cotton, 12 oz denim</li><li>Fit: Straight</li><li>Ages: 5–6, 6–7, 7–8, 8–9, 9–10, 10–11 yrs</li><li>Pockets: 7 (inc. 2 cargo zip)</li><li>Care: Machine wash cold</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_JEANS,'type'=>'jeans','rgb'=>[65,85,145],'sku'=>'KJN-008','price'=>24.99,
  'title'=>"Kids' Pull-On Jeggings",'tagline'=>'Soft jegging comfort with a convincing denim look.',
  'body'=>'<p>Jeggings that look like jeans but feel like leggings. A comfortable blend of cotton, polyester, and elastane gives full stretch, while the faux-fly stitching and front pockets keep the denim aesthetic intact.</p><ul><li>Material: 72% Cotton, 22% Polyester, 6% Elastane</li><li>Fit: Slim / Jegging</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8 yrs</li><li>Waist: Fully elasticated</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_JEANS,'type'=>'jeans','rgb'=>[45,65,125],'sku'=>'KJN-009','price'=>35.99,
  'title'=>"Kids' Dungaree Denim Overalls",'tagline'=>'Classic bib-and-brace dungarees in soft pre-washed denim.',
  'body'=>'<p>Timeless bib dungarees in pre-washed cotton denim. Adjustable shoulder straps, snap-fastening inner leg for easy changes, a large bib pocket, and two side patch pockets. Available in blue, light wash, and pink denim.</p><ul><li>Material: 98% Cotton, 2% Elastane, pre-washed</li><li>Fit: Relaxed bib</li><li>Ages: 1–2, 2–3, 3–4, 4–5, 5–6, 6–7 yrs</li><li>Care: Machine wash cold</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_JEANS,'type'=>'jeans','rgb'=>[35,55,110],'sku'=>'KJN-010','price'=>29.99,
  'title'=>"Kids' Wide Leg Barrel Jeans",'tagline'=>'Trendy barrel-leg silhouette scaled for kids.',
  'body'=>'<p>A fashion-forward barrel-leg cut with a high rise and extra width through the hip and thigh, tapering gently at the ankle. Scaled perfectly for children. Five-pocket design in a vintage indigo wash.</p><ul><li>Material: 100% Cotton</li><li>Fit: Wide Barrel Leg, High Rise</li><li>Ages: 5–6, 6–7, 7–8, 8–9, 9–10, 10–11 yrs</li><li>Wash: Vintage Indigo</li><li>Care: Machine wash cold</li></ul>'];

// ─────────────────────────────────────────────────────────────────────────────
// KIDS' SHORTS
// ─────────────────────────────────────────────────────────────────────────────
$catalog[] = ['tid'=>$TID_KIDS_SHORTS,'type'=>'shorts','rgb'=>[30,120,200],'sku'=>'KSH-001','price'=>14.99,
  'title'=>"Kids' Jersey Sport Shorts",'tagline'=>'Lightweight moisture-wicking jersey for PE and play.',
  'body'=>'<p>An everyday sports short in lightweight polyester jersey with a full elasticated waistband and a drawstring for adjustable fit. Moisture-wicking fabric keeps kids dry during PE, football, and garden play.</p><ul><li>Material: 100% Polyester, moisture-wicking</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Inseam: 3 inches</li><li>Colors: Navy, Royal Blue, Black, Red</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHORTS,'type'=>'shorts','rgb'=>[80,110,175],'sku'=>'KSH-002','price'=>19.99,
  'title'=>"Kids' Denim Cutoff Shorts",'tagline'=>'Fray-hem denim cutoffs for summer adventures.',
  'body'=>'<p>Classic five-pocket denim shorts with a raw fray hem and an elasticated adjustment inside the waistband. Mid-blue wash and a relaxed fit that pairs with everything from sandals to trainers.</p><ul><li>Material: 100% Cotton denim</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Mid Blue, Light Wash</li><li>Care: Machine wash cold</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHORTS,'type'=>'shorts','rgb'=>[60,160,220],'sku'=>'KSH-003','price'=>16.99,
  'title'=>"Kids' Quick-Dry Swim Shorts",'tagline'=>'Fast-drying boardshorts with UPF 50+ sun protection.',
  'body'=>'<p>Made from lightweight recycled polyester that dries in under 20 minutes, these boardshorts are built for pool and beach. UPF 50+ fabric, secure Velcro-and-drawstring waistband, and a small zip back pocket.</p><ul><li>Material: 100% Recycled Polyester, quick-dry</li><li>UPF: 50+</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Inseam: 5 inches</li><li>Colors: Tropical Print, Camo Blue, Solid Navy</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHORTS,'type'=>'shorts','rgb'=>[60,80,50],'sku'=>'KSH-004','price'=>21.99,
  'title'=>"Kids' Cargo Shorts",'tagline'=>'Multi-pocket cargo shorts built for maximising adventures.',
  'body'=>'<p>Featuring two large velcro-fastened cargo pockets in addition to standard front and rear pockets, these shorts are built for kids who need storage space. Elasticated waist with an external drawstring and a durable cotton-blend fabric.</p><ul><li>Material: 65% Cotton, 35% Polyester</li><li>Ages: 5–6, 6–7, 7–8, 8–9, 9–10, 10–11 yrs</li><li>Pockets: 6 total (2 cargo)</li><li>Colors: Khaki, Olive, Stone, Navy</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHORTS,'type'=>'shorts','rgb'=>[240,120,160],'sku'=>'KSH-005','price'=>15.99,
  'title'=>"Kids' Floral Print Shorts",'tagline'=>'Vibrant floral print woven shorts for sunny days.',
  'body'=>'<p>A fun floral print on a lightweight woven cotton shorts with an elasticated waist and an internal adjustable button. Neat straight hem and two side pockets. Perfect for holidays and warm school days.</p><ul><li>Material: 100% Woven Cotton</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9 yrs</li><li>Colors: Tropical Floral, Ditsy Floral Yellow, Blue Floral</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHORTS,'type'=>'shorts','rgb'=>[200,160,50],'sku'=>'KSH-006','price'=>18.99,
  'title'=>"Kids' Chino Shorts",'tagline'=>'Smart elasticated chino shorts — great for school and occasions.',
  'body'=>'<p>Smarter than jersey but more comfortable than stiff denim, these chino shorts in a stretch-cotton twill are a versatile choice for everything from school events to family trips. Internal adjustable waist and two side pockets.</p><ul><li>Material: 97% Cotton, 3% Elastane twill</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Khaki, Navy, Stone, Pale Blue</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHORTS,'type'=>'shorts','rgb'=>[80,60,150],'sku'=>'KSH-007','price'=>16.99,
  'title'=>"Kids' Cycling Shorts",'tagline'=>'Stretchy compression cycling shorts for active little riders.',
  'body'=>'<p>Lightweight compression shorts made from a soft, stretchy nylon-elastane blend. A flat waistband sits comfortably and won\'t fold, and the smooth fabric doesn\'t chafe during cycling, gymnastics, or dance. Worn under skirts, dresses, or solo.</p><ul><li>Material: 80% Nylon, 20% Elastane</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Black, Navy, Dusty Pink, White</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHORTS,'type'=>'shorts','rgb'=>[60,190,140],'sku'=>'KSH-008','price'=>17.99,
  'title'=>"Kids' Tie-Dye Jersey Shorts",'tagline'=>'Soft jersey shorts with a hand-dyed spiral tie-dye effect.',
  'body'=>'<p>Cotton-jersey pull-on shorts with a colourful tie-dye swirl pattern. Elasticated waistband, a 4-inch inseam, and a relaxed fit that works for lounging, play, and beach days.</p><ul><li>Material: 100% Cotton jersey</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9 yrs</li><li>Colors: Sunrise, Ocean, Berry, Lime</li><li>Care: Machine wash cold; colours may bleed first wash</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHORTS,'type'=>'shorts','rgb'=>[200,200,180],'sku'=>'KSH-009','price'=>19.99,
  'title'=>"Kids' Linen Blend Shorts",'tagline'=>'Breathable linen-cotton mix — keeps cool in the summer heat.',
  'body'=>'<p>Made from a linen-cotton blend, these shorts are supremely breathable and lightweight — perfect for hot summer days. Elasticated waistband, side pockets, and a tailored straight hem.</p><ul><li>Material: 55% Linen, 45% Cotton</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9 yrs</li><li>Colors: Natural Ecru, Pale Blue, Soft Pink, Sage Green</li><li>Care: Machine wash 30°C; press while slightly damp</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHORTS,'type'=>'shorts','rgb'=>[150,60,60],'sku'=>'KSH-010','price'=>15.99,
  'title'=>"Kids' Roll-Hem Jersey Shorts",'tagline'=>'Cotton jersey shorts with an easy rolled hem and neat fit.',
  'body'=>'<p>Everyday jersey shorts with a clean rolled hem, elasticated waistband, and a straight leg. Simple, comfortable, and machine-washable — an easy wardrobe staple in classic solid colours.</p><ul><li>Material: 100% Cotton jersey, 180 gsm</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8, 8–9 yrs</li><li>Colors: Red, White, Black, Navy, Jade Green</li><li>Care: Machine wash 40°C</li></ul>'];

// ─────────────────────────────────────────────────────────────────────────────
// KIDS' HOODIES
// ─────────────────────────────────────────────────────────────────────────────
$catalog[] = ['tid'=>$TID_KIDS_HOODIES,'type'=>'hoodie','rgb'=>[200,100,180],'sku'=>'KHD-001','price'=>29.99,
  'title'=>"Kids' Rainbow Zip-Up Hoodie",'tagline'=>'Vibrant rainbow stripe full-zip in cosy brushed fleece.',
  'body'=>'<p>A cheerful full-zip hoodie in a bright rainbow stripe brushed cotton-blend fleece. Easy zip-front for hassle-free dressing, kangaroo pocket, and ribbed cuffs and hem for a snug fit.</p><ul><li>Material: 70% Cotton, 30% Polyester fleece</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Rainbow Stripe</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_HOODIES,'type'=>'hoodie','rgb'=>[60,80,50],'sku'=>'KHD-002','price'=>27.99,
  'title'=>"Kids' Camo Pullover Hoodie",'tagline'=>'Classic camo print hoodie in soft organic cotton fleece.',
  'body'=>'<p>A pullover hoodie in a classic camouflage print on a soft organic cotton fleece. Drawstring hood, kangaroo pocket, and ribbed cuffs. The fleece interior is brushed for extra warmth.</p><ul><li>Material: 80% Organic Cotton, 20% Polyester</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Military Camo, Urban Camo Grey</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_HOODIES,'type'=>'hoodie','rgb'=>[50,100,200],'sku'=>'KHD-003','price'=>31.99,
  'title'=>"Kids' Character Print Hoodie",'tagline'=>'Beloved character face print on a plush cotton hoodie.',
  'body'=>'<p>A fun, large-format character face print covers the chest and hood of this soft cotton-blend hoodie. Features a full zip for easy wearing, hand pockets, and a brushed fleece interior lining.</p><ul><li>Material: 70% Cotton, 30% Polyester fleece</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8, 8–9 yrs</li><li>Colors: Blue base, Red base, Yellow base</li><li>Care: Machine wash 40°C, do not iron print</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_HOODIES,'type'=>'hoodie','rgb'=>[170,170,200],'sku'=>'KHD-004','price'=>34.99,
  'title'=>"Kids' Fleece-Lined Pullover Hoodie",'tagline'=>'320 gsm cotton-rich fleece for extra warmth on cold days.',
  'body'=>'<p>A midweight 320 gsm cotton-rich fleece pullover with a brushed interior for maximum comfort. Drawstring hood, kangaroo pocket, and ribbed cuffs and hem. Great for layering under a waterproof jacket.</p><ul><li>Material: 80% Cotton, 20% Polyester, 320 gsm</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Charcoal, Navy, Grey Marl, Dusty Pink</li><li>Care: Machine wash 30°C, tumble dry low</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_HOODIES,'type'=>'hoodie','rgb'=>[230,140,60],'sku'=>'KHD-005','price'=>28.99,
  'title'=>"Kids' Tie-Dye Pullover Hoodie",'tagline'=>'Hand-dipped tie-dye swirl — uniquely yours.',
  'body'=>'<p>Every hoodie is individually dipped and knotted to create a unique swirling pattern. Soft 100% organic cotton jersey with a relaxed pullover cut, hood with drawstring, and a front kangaroo pocket.</p><ul><li>Material: 100% GOTS Organic Cotton</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Sunrise, Ocean, Berry, Lime</li><li>Care: Machine wash cold; wash separately first time</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_HOODIES,'type'=>'hoodie','rgb'=>[80,50,130],'sku'=>'KHD-006','price'=>26.99,
  'title'=>"Kids' Galaxy Print Hoodie",'tagline'=>'Cosmic galaxy all-over print with glow-in-the-dark stars.',
  'body'=>'<p>An all-over galaxy print in deep purples and blues features glow-in-the-dark star details that light up at night. Soft cotton-blend fleece interior, drawstring hood, and kangaroo pocket.</p><ul><li>Material: 70% Cotton, 30% Polyester</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Midnight Galaxy</li><li>Care: Machine wash 30°C, inside out</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_HOODIES,'type'=>'hoodie','rgb'=>[200,60,60],'sku'=>'KHD-007','price'=>32.99,
  'title'=>"Kids' Varsity Logo Hoodie",'tagline'=>'Classic varsity-style block letter print on soft fleece.',
  'body'=>'<p>A varsity-inspired hoodie with a block letter chest graphic and number print. Made from a midweight cotton-blend fleece with a brushed interior. Full zip, ribbed collar, cuffs, and hem.</p><ul><li>Material: 80% Cotton, 20% Polyester fleece</li><li>Ages: 5–6, 6–7, 7–8, 8–9, 9–10, 10–11 yrs</li><li>Colors: Red/White, Navy/Yellow, Black/Orange</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_HOODIES,'type'=>'hoodie','rgb'=>[40,120,160],'sku'=>'KHD-008','price'=>39.99,
  'title'=>"Kids' Windproof Outdoor Hoodie",'tagline'=>'Windproof softshell hoodie for active outdoor adventures.',
  'body'=>'<p>A windproof and water-resistant softshell hoodie designed for outdoor activity — hiking, cycling, and playground adventures alike. Bonded fleece interior, zip pockets, and a stretch fabric that moves with every jump and climb.</p><ul><li>Material: Softshell polyester, bonded fleece lining</li><li>Windproof: Yes; Water resistant: Yes (DWR)</li><li>Ages: 5–6, 6–7, 7–8, 8–9, 9–10, 10–11 yrs</li><li>Colors: Ocean Blue, Forest Green, Charcoal</li><li>Care: Machine wash 30°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_HOODIES,'type'=>'hoodie','rgb'=>[100,170,100],'sku'=>'KHD-009','price'=>24.99,
  'title'=>"Kids' Striped Pullover Hoodie",'tagline'=>'Bold stripe pullover hoodie in soft 100% cotton jersey.',
  'body'=>'<p>A pullover hoodie in a bold colour-block stripe. Made from a soft 100% cotton jersey — lightweight enough for spring and summer evenings but cosy enough for cooler days. Hood with drawstring and kangaroo pocket.</p><ul><li>Material: 100% Cotton jersey, 220 gsm</li><li>Ages: 2–3, 3–4, 4–5, 5–6, 6–7, 7–8, 8–9 yrs</li><li>Colors: Navy/White Stripe, Green/White Stripe, Red/White Stripe</li><li>Care: Machine wash 40°C</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_HOODIES,'type'=>'hoodie','rgb'=>[220,200,160],'sku'=>'KHD-010','price'=>36.99,
  'title'=>"Kids' Sherpa Fleece Hoodie",'tagline'=>'Cloud-soft sherpa fleece interior — ultra cosy for winter.',
  'body'=>'<p>A thick, cloud-soft sherpa fleece pullover with a smooth outer shell and a luxuriously fluffy interior lining. Perfect for cold winter mornings and evening walks. Kangaroo pocket, ribbed cuffs and hem.</p><ul><li>Shell: 100% Polyester</li><li>Lining: Sherpa fleece</li><li>Ages: 3–4, 4–5, 5–6, 6–7, 7–8, 8–9, 9–10 yrs</li><li>Colors: Cream, Tan, Grey, Blush Pink</li><li>Care: Machine wash 30°C, gentle cycle</li></ul>'];

// ─────────────────────────────────────────────────────────────────────────────
// KIDS' SHOES
// ─────────────────────────────────────────────────────────────────────────────
$catalog[] = ['tid'=>$TID_KIDS_SHOES,'type'=>'shoes','rgb'=>[240,240,240],'sku'=>'KSE-001','price'=>34.99,
  'title'=>"Kids' Canvas Lace-Up Sneakers",'tagline'=>'Classic vulcanised canvas low-top for little feet.',
  'body'=>'<p>A kids\' take on the classic canvas sneaker: cotton canvas upper, vulcanised rubber sole, cushioned insole, and an easy-on elastic lace system. Flexible and lightweight for all-day comfort.</p><ul><li>Upper: 100% Cotton Canvas</li><li>Sole: Natural rubber, vulcanised</li><li>Sizes: UK 6 Infant – UK 5 Junior</li><li>Colors: White, Red, Black, Sky Blue</li><li>Care: Spot clean with damp cloth</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHOES,'type'=>'shoes','rgb'=>[50,130,220],'sku'=>'KSE-002','price'=>39.99,
  'title'=>"Kids' Velcro Strap Trainers",'tagline'=>'Double Velcro strap trainers for independent dressers.',
  'body'=>'<p>Designed for independence, these trainers feature two easy-fasten Velcro straps so kids can put them on themselves. Breathable mesh upper, cushioned EVA midsole, and a flex-groove rubber outsole for natural foot movement.</p><ul><li>Upper: Breathable mesh + synthetic overlay</li><li>Midsole: EVA cushioning</li><li>Sole: Flex-groove rubber</li><li>Sizes: UK 5 Infant – UK 3 Junior</li><li>Colors: Blue/White, Black/Red, Pink/White</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHOES,'type'=>'shoes','rgb'=>[200,50,50],'sku'=>'KSE-003','price'=>29.99,
  'title'=>"Kids' Wellington Rain Boots",'tagline'=>'Waterproof neoprene-lined wellies — perfect for puddles.',
  'body'=>'<p>100% waterproof rubber Wellington boots with a warm neoprene lining for cold and wet weather. Lightweight and flexible with a wide-mouth opening for easy on-and-off. Slip-resistant sole rated for wet surfaces.</p><ul><li>Upper: Natural rubber</li><li>Lining: Neoprene warmth lining</li><li>Sole: Anti-slip rubber</li><li>Sizes: UK 6 Infant – UK 5 Junior</li><li>Colors: Red, Navy, Green, Black, Yellow</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHOES,'type'=>'shoes','rgb'=>[30,30,30],'sku'=>'KSE-004','price'=>44.99,
  'title'=>"Kids' Leather School Shoes",'tagline'=>'Durable leather school shoes with cushioned sole.',
  'body'=>'<p>Designed for durability during the school day, these shoes feature a genuine leather upper, padded collar for comfort, and a cushioned, non-marking rubber outsole. Easy buckle or lace fastening depending on size.</p><ul><li>Upper: Genuine leather</li><li>Lining: Breathable textile</li><li>Sole: Non-marking rubber</li><li>Sizes: UK 8 Infant – UK 6 Junior</li><li>Colors: Black, Brown, Patent Black</li><li>Care: Polish regularly; wipe clean with damp cloth</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHOES,'type'=>'shoes','rgb'=>[60,200,120],'sku'=>'KSE-005','price'=>42.99,
  'title'=>"Kids' Sport Running Shoes",'tagline'=>'Lightweight knit running shoes for active training.',
  'body'=>'<p>Engineered for young runners, these training shoes feature a breathable knit upper, responsive cushioned midsole, and a durable rubber outsole. Lightweight construction minimises fatigue during sports and PE.</p><ul><li>Upper: Engineered knit</li><li>Midsole: Responsive EVA foam</li><li>Outsole: Rubber, multi-directional grip</li><li>Weight: 220 g (UK 3 Junior)</li><li>Sizes: UK 10 Infant – UK 6 Junior</li><li>Colors: Black/Neon, White/Blue, Grey/Orange</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHOES,'type'=>'shoes','rgb'=>[255,180,50],'sku'=>'KSE-006','price'=>24.99,
  'title'=>"Kids' Light-Up LED Trainers",'tagline'=>'Built-in LED lights that flash with every step.',
  'body'=>'<p>A kids\' favourite: battery-powered LEDs in the sole that flash with each step. Breathable mesh upper, single Velcro strap, and cushioned insole. The LED module is built into the heel and requires no charging — batteries last 12 months of regular use.</p><ul><li>Upper: Breathable mesh</li><li>Sole: EVA with integrated LED module</li><li>Sizes: UK 5 Infant – UK 2 Junior</li><li>Colors: Blue, Pink, Red</li><li>Features: Flashing LEDs, Velcro strap</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHOES,'type'=>'shoes','rgb'=>[50,50,50],'sku'=>'KSE-007','price'=>37.99,
  'title'=>"Kids' Chelsea Boots",'tagline'=>'Pull-on Chelsea boots in durable faux suede.',
  'body'=>'<p>A miniature version of the iconic Chelsea boot, made from durable faux suede with twin elasticated gussets for easy pull-on wear. A short block heel, textile lining, and flex-groove rubber outsole for grip on wet pavements.</p><ul><li>Upper: Faux suede</li><li>Lining: Textile</li><li>Sole: Flex rubber</li><li>Sizes: UK 5 Infant – UK 4 Junior</li><li>Colors: Black, Tan, Grey, Dusty Pink</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHOES,'type'=>'shoes','rgb'=>[220,190,140],'sku'=>'KSE-008','price'=>27.99,
  'title'=>"Kids' Slip-On Summer Sandals",'tagline'=>'Lightweight two-strap sandals for hot summer days.',
  'body'=>'<p>Simple and comfortable two-strap sandals made from soft synthetic leather with padded footbeds and adjustable velcro straps. Non-slip rubber outsole for poolside and beach safety. Open-toe design for maximum breathability.</p><ul><li>Upper: Synthetic leather straps</li><li>Footbed: Cushioned EVA</li><li>Sole: Non-slip rubber</li><li>Sizes: UK 5 Infant – UK 5 Junior</li><li>Colors: Tan, White, Navy, Pink</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHOES,'type'=>'shoes','rgb'=>[100,60,30],'sku'=>'KSE-009','price'=>49.99,
  'title'=>"Kids' Winter Snow Boots",'tagline'=>'-20°C rated snow boots with waterproof shell and fleece lining.',
  'body'=>'<p>Built for winter: a waterproof nylon outer shell with taped seams, a 200 g fleece-and-Thinsulate lining rated to -20°C, and an aggressive lug outsole for grip on snow and ice. Quick-lace toggle for easy on-and-off.</p><ul><li>Upper: Waterproof nylon, taped seams</li><li>Lining: Fleece + 200 g Thinsulate</li><li>Temp rating: -20°C</li><li>Sole: Anti-slip lug rubber</li><li>Sizes: UK 6 Infant – UK 5 Junior</li><li>Colors: Navy/Grey, Red/Black, Pink/Purple</li></ul>'];

$catalog[] = ['tid'=>$TID_KIDS_SHOES,'type'=>'shoes','rgb'=>[170,100,200],'sku'=>'KSE-010','price'=>32.99,
  'title'=>"Kids' Glitter High-Top Sneakers",'tagline'=>'Sparkly glitter high-tops that make every step a celebration.',
  'body'=>'<p>A glitter-finish canvas high-top with a star patch at the ankle and a padded collar for comfort. Lace-up front with a side zip for quick on-and-off. Cushioned insole and a flexible rubber outsole.</p><ul><li>Upper: Glitter canvas + star ankle patch</li><li>Closure: Lace-up + side zip</li><li>Sole: Flexible rubber</li><li>Sizes: UK 5 Infant – UK 4 Junior</li><li>Colors: Silver Glitter, Gold Glitter, Rose Gold, Pink Glitter</li></ul>'];

// ═══════════════════════════════════════════════════════════════════════════════
// STEP 3 — generate images + create products
// ═══════════════════════════════════════════════════════════════════════════════
$subcat_labels = [
  $TID_MEN_JEANS   => "Men's Jeans",
  $TID_MEN_TSHIRTS => "Men's T-Shirts",
  $TID_MEN_SHIRTS  => "Men's Shirts",
  $TID_MEN_JACKETS => "Men's Jackets",
  $TID_MEN_SHOES   => "Men's Shoes",
  $TID_KIDS_TSHIRTS=> "Kids' T-Shirts",
  $TID_KIDS_JEANS  => "Kids' Jeans",
  $TID_KIDS_SHORTS => "Kids' Shorts",
  $TID_KIDS_HOODIES=> "Kids' Hoodies",
  $TID_KIDS_SHOES  => "Kids' Shoes",
];

$current_tid = 0;
foreach ($catalog as $item) {
  if ($item['tid'] !== $current_tid) {
    $current_tid = $item['tid'];
    echo "\n=== " . ($subcat_labels[$current_tid] ?? "TID $current_tid") . " ===\n";
  }
  $file = make_image($item['title'], $item['rgb'], $item['type'], "product-{$idx}.png");
  $idx++;
  make_product([
    'tid'     => $item['tid'],
    'sku'     => $item['sku'],
    'price'   => $item['price'],
    'title'   => $item['title'],
    'tagline' => $item['tagline'],
    'body'    => $item['body'],
    'file'    => $file,
  ]);
}

$total = count($catalog);
echo "\n✓ Done — {$total} products processed. Run: ddev drush cr\n";
