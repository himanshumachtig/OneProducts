<?php
/**
 * Regenerate product-29.png → product-128.png with recognisable silhouettes.
 * Overwrites files in-place; Drupal file entities are untouched.
 * Run: ddev drush php-script regen_product_images.php
 */

// ── Polyfill ──────────────────────────────────────────────────────────────────
if (!function_exists('imagefilledroundedrectangle')) {
  function imagefilledroundedrectangle($img, $x1, $y1, $x2, $y2, $r, $color) {
    imagefilledrectangle($img, $x1+$r, $y1,   $x2-$r, $y2,   $color);
    imagefilledrectangle($img, $x1,   $y1+$r, $x2,    $y2-$r,$color);
    imagefilledellipse($img,$x1+$r,$y1+$r,$r*2,$r*2,$color);
    imagefilledellipse($img,$x2-$r,$y1+$r,$r*2,$r*2,$color);
    imagefilledellipse($img,$x1+$r,$y2-$r,$r*2,$r*2,$color);
    imagefilledellipse($img,$x2-$r,$y2-$r,$r*2,$r*2,$color);
  }
}

// ── Core renderer ─────────────────────────────────────────────────────────────
function render_image(string $type, array $rgb, string $label, string $filepath): void {
  [$r,$g,$b] = $rgb;

  $img   = imagecreatetruecolor(400, 400);
  $bgc   = imagecolorallocate($img, 245, 245, 248);   // near-white background
  $col   = imagecolorallocate($img, $r, $g, $b);
  $dark  = imagecolorallocate($img, max(0,$r-70), max(0,$g-70), max(0,$b-70));
  $vdark = imagecolorallocate($img, max(0,$r-120),max(0,$g-120),max(0,$b-120));
  $light = imagecolorallocate($img, min(255,$r+70), min(255,$g+70), min(255,$b+70));
  $white = imagecolorallocate($img, 255,255,255);
  $grey  = imagecolorallocate($img, 170,170,170);
  $dkgr  = imagecolorallocate($img, 80, 80, 80);
  // For very light shirts use grey outline so shape is visible
  $outline = ($r+$g+$b > 580) ? imagecolorallocate($img,130,130,130) : $dark;

  imagefilledrectangle($img, 0, 0, 400, 400, $bgc);

  // ── Footer label bar ──────────────────────────────────────────────────────
  imagefilledrectangle($img, 0, 360, 400, 400, $dark);
  $words = explode(' ', $label);
  $lines=[]; $ln='';
  foreach($words as $w){
    if($ln!=='' && strlen("$ln $w")>26){$lines[]=$ln;$ln=$w;}
    else{$ln=$ln===''?$w:"$ln $w";}
  }
  $lines[]=$ln;
  $ty = 362 + (int)((38 - count($lines)*16)/2);
  foreach($lines as $l){
    $tw=(int)(imagefontwidth(3)*strlen($l));
    imagestring($img,3,(400-$tw)/2,$ty,$l,$white);
    $ty+=16;
  }

  switch ($type) {

    // ════════════════════════════════════════════════════════════
    // T-SHIRT
    // Classic crew-neck tee with short set-in sleeves
    // ════════════════════════════════════════════════════════════
    case 'tshirt':
      // Main silhouette (body + sleeves as one polygon)
      imagefilledpolygon($img, [
        148, 95,   // left collar
        122, 65,   // left shoulder
         58, 48,   // left sleeve top-outer
         42,155,   // left sleeve bottom-outer
        115,172,   // left armhole
        115,340,   // left hem
        285,340,   // right hem
        285,172,   // right armhole
        358,155,   // right sleeve bottom-outer
        342, 48,   // right sleeve top-outer
        278, 65,   // right shoulder
        252, 95,   // right collar
      ], $col);
      // Neck opening (cut with bg)
      imagefilledellipse($img, 200, 88, 110, 58, $bgc);
      // Neck-rib arc
      imagearc($img, 200, 78, 110, 58, 5, 175, $outline);
      // Seam lines
      imageline($img,115,172,115,340,$outline); // left side seam
      imageline($img,285,172,285,340,$outline); // right side seam
      imageline($img,115,340,285,340,$outline); // hem
      imageline($img, 42,155,115,172,$outline); // left sleeve seam
      imageline($img,285,172,358,155,$outline); // right sleeve seam
      break;

    // ════════════════════════════════════════════════════════════
    // SHIRT – long sleeves, spread collar, buttons
    // ════════════════════════════════════════════════════════════
    case 'shirt':
      // Body + long sleeves
      imagefilledpolygon($img, [
        148, 92,   // left collar
        118, 62,   // left shoulder
         55,240,   // left cuff outer
         78,260,   // left cuff inner
        118,175,   // left armhole
        118,345,   // left hem
        282,345,   // right hem
        282,175,   // right armhole
        322,260,   // right cuff inner
        345,240,   // right cuff outer
        282, 62,   // right shoulder
        252, 92,   // right collar
      ], $col);
      // Left collar flap
      imagefilledpolygon($img,[148,92,200,148,170,92],$light);
      // Right collar flap
      imagefilledpolygon($img,[252,92,200,148,230,92],$light);
      // Collar outline
      imageline($img,148,92,200,148,$outline);
      imageline($img,200,148,252,92,$outline);
      // Button placket line
      imageline($img,200,148,200,345,$outline);
      // Buttons
      foreach([175,210,245,280,315] as $by)
        imagefilledellipse($img,200,$by,9,9,$outline);
      // Side seams
      imageline($img,118,175,118,345,$outline);
      imageline($img,282,175,282,345,$outline);
      imageline($img,118,345,282,345,$outline);
      // Sleeve seam lines
      imageline($img, 55,240, 78,260,$outline);
      imageline($img,322,260,345,240,$outline);
      break;

    // ════════════════════════════════════════════════════════════
    // JEANS – two legs, waistband, fly, pockets
    // ════════════════════════════════════════════════════════════
    case 'jeans':
      // Main shape: waistband + two legs with crotch V
      imagefilledpolygon($img, [
        108, 55,   // left waist
        292, 55,   // right waist
        292,350,   // right leg outer bottom
        222,350,   // right leg inner bottom
        200,182,   // crotch apex
        178,350,   // left leg inner bottom
        108,350,   // left leg outer bottom
      ], $col);
      // Waistband darker band
      imagefilledrectangle($img, 108, 55, 292, 100, $dark);
      // Waistband top edge
      imageline($img,108,55,292,55,$vdark);
      // Belt loops (3)
      foreach([140,200,260] as $bx){
        imagefilledrectangle($img,$bx-7,52,$bx+7,104,$light);
        imagefilledrectangle($img,$bx-5,55,$bx+5,100,$dark);
      }
      // Top button
      imagefilledellipse($img,200,73,14,14,$light);
      imagefilledellipse($img,200,73, 8, 8,$vdark);
      // Fly line from button to crotch
      imageline($img,200, 87,200,182,$vdark);
      // Left pocket arc
      imagearc($img,132,107,90,55,0,95,$light);
      // Right pocket arc
      imagearc($img,268,107,90,55,85,180,$light);
      // Outer leg seams
      imageline($img,108,100,108,350,$vdark);
      imageline($img,292,100,292,350,$vdark);
      // Inner leg seams (both sides from crotch down)
      imageline($img,200,182,178,350,$vdark);
      imageline($img,200,182,222,350,$vdark);
      // Bottom hem lines
      imageline($img,108,350,178,350,$vdark);
      imageline($img,222,350,292,350,$vdark);
      break;

    // ════════════════════════════════════════════════════════════
    // SHORTS – like jeans but legs end mid-thigh
    // ════════════════════════════════════════════════════════════
    case 'shorts':
      imagefilledpolygon($img, [
        108, 55,
        292, 55,
        292,240,
        222,240,
        200,165,
        178,240,
        108,240,
      ], $col);
      // Waistband
      imagefilledrectangle($img,108,55,292,100,$dark);
      imageline($img,108,55,292,55,$vdark);
      // Belt loops
      foreach([140,200,260] as $bx){
        imagefilledrectangle($img,$bx-7,52,$bx+7,104,$light);
        imagefilledrectangle($img,$bx-5,55,$bx+5,100,$dark);
      }
      // Button
      imagefilledellipse($img,200,73,14,14,$light);
      imagefilledellipse($img,200,73, 8, 8,$vdark);
      // Fly
      imageline($img,200,87,200,165,$vdark);
      // Pocket arcs
      imagearc($img,132,107,90,55,0,95,$light);
      imagearc($img,268,107,90,55,85,180,$light);
      // Seams
      imageline($img,108,100,108,240,$vdark);
      imageline($img,292,100,292,240,$vdark);
      imageline($img,200,165,178,240,$vdark);
      imageline($img,200,165,222,240,$vdark);
      imageline($img,108,240,178,240,$vdark);
      imageline($img,222,240,292,240,$vdark);
      break;

    // ════════════════════════════════════════════════════════════
    // JACKET – body with wide lapels, zip, pockets
    // ════════════════════════════════════════════════════════════
    case 'jacket':
      // Body + long sleeves
      imagefilledpolygon($img, [
        145, 88,   // left collar
        115, 60,   // left shoulder
         50,245,   // left cuff outer
         75,265,   // left cuff inner
        115,175,   // left armhole
        115,345,   // left hem
        285,345,   // right hem
        285,175,   // right armhole
        325,265,   // right cuff inner
        350,245,   // right cuff outer
        285, 60,   // right shoulder
        255, 88,   // right collar
      ], $col);
      // Left lapel
      imagefilledpolygon($img,[145,88,200,165,160,88],$light);
      imageline($img,145,88,200,165,$outline);
      // Right lapel
      imagefilledpolygon($img,[255,88,200,165,240,88],$light);
      imageline($img,200,165,255,88,$outline);
      // Collar stand (between lapels at top)
      imagefilledpolygon($img,[160,88,145,88,148,68,200,75,252,68,255,88,240,88,200,105],$dark);
      // Zip line
      imageline($img,200,165,200,345,$dark);
      // Hip pockets
      imagefilledroundedrectangle($img,122,262,175,285,5,$dark);
      imageline($img,122,262,175,262,$light);
      imagefilledroundedrectangle($img,225,262,278,285,5,$dark);
      imageline($img,225,262,278,262,$light);
      // Side seams
      imageline($img,115,175,115,345,$outline);
      imageline($img,285,175,285,345,$outline);
      imageline($img,115,345,285,345,$outline);
      break;

    // ════════════════════════════════════════════════════════════
    // HOODIE – t-shirt body + hood + kangaroo pocket
    // ════════════════════════════════════════════════════════════
    case 'hoodie':
      // Body + sleeves (same as tshirt)
      imagefilledpolygon($img, [
        148, 95,
        122, 65,
         58, 48,
         42,155,
        115,172,
        115,340,
        285,340,
        285,172,
        358,155,
        342, 48,
        278, 65,
        252, 95,
      ], $col);
      // Hood (large D-shape sitting on shoulders)
      imagefilledarc($img,200,95,175,155,185,355,$col,IMG_ARC_PIE);
      // Hood opening (inner circle, bg colour)
      imagefilledellipse($img,200,95,105,105,$bgc);
      // Hood outline arc
      imagearc($img,200,95,175,155,185,355,$dark);
      imagearc($img,200,95,105,105,185,355,$dark);
      // Drawstring cords
      imageline($img,168,98,148,130,$dark);
      imageline($img,232,98,252,130,$dark);
      // Drawstring tips
      imagefilledellipse($img,148,133,8,8,$dark);
      imagefilledellipse($img,252,133,8,8,$dark);
      // Kangaroo pocket
      imagefilledroundedrectangle($img,140,265,260,320,8,$dark);
      imageline($img,140,265,260,265,$light);
      imageline($img,200,265,200,320,$dark); // pocket divider
      // Side seams
      imageline($img,115,172,115,340,$outline);
      imageline($img,285,172,285,340,$outline);
      imageline($img,115,340,285,340,$outline);
      break;

    // ════════════════════════════════════════════════════════════
    // SHOES – side-profile sneaker/boot facing right
    // ════════════════════════════════════════════════════════════
    case 'shoes':
      // Thick sole (platform)
      imagefilledpolygon($img,[
        55,290, 365,290, 360,325, 50,325
      ],$dark);
      // Midsole stripe
      imagefilledrectangle($img,55,285,365,292,$light);
      // Upper body (main shoe shape)
      imagefilledpolygon($img,[
         62,290,  // heel bottom
         55,215,  // heel back lower
         68,185,  // heel back upper
        100,162,  // heel top
        178,150,  // midfoot top back
        240,155,  // midfoot top front
        305,168,  // toe area
        350,205,  // toe tip
        358,290,  // toe bottom
      ],$col);
      // Tongue (front of shoe)
      imagefilledpolygon($img,[
        178,150, 240,155, 242,220, 176,220
      ],$light);
      // Lace eyelets + laces (4 rows)
      for($i=0;$i<4;$i++){
        $ly = 168+$i*14;
        $lx1=182; $lx2=236;
        imagefilledellipse($img,$lx1,$ly,9,9,$dark);
        imagefilledellipse($img,$lx2,$ly,9,9,$dark);
        // crisscross lace
        if($i%2===0) imageline($img,$lx1+4,$ly,$lx2-4,$ly+14,$white);
        else imageline($img,$lx1+4,$ly,$lx2-4,$ly-14,$white);
      }
      // Heel tab
      imagefilledrectangle($img,58,165,72,215,$light);
      // Toe cap
      imagearc($img,310,210,110,90,300,80,$dark);
      // Sole stitch line
      imageline($img,55,290,365,290,$light);
      // Outline the upper
      imageline($img,62,290,55,215,$dark);
      imageline($img,55,215,68,185,$dark);
      imageline($img,68,185,100,162,$dark);
      imageline($img,100,162,178,150,$dark);
      imageline($img,240,155,305,168,$dark);
      imageline($img,305,168,350,205,$dark);
      imageline($img,350,205,358,290,$dark);
      break;
  }

  imagepng($img, $filepath);
  imagedestroy($img);
  echo "  $filepath\n";
}

// ═══════════════════════════════════════════════════════════════════════════════
// CATALOG — must match exact order from add_men_kids_products.php
// ═══════════════════════════════════════════════════════════════════════════════
$catalog = [
  // Men's Jeans (files 29-38)
  ['jeans',[40,70,130],"Men's Slim Fit Dark Wash Jeans"],
  ['jeans',[80,100,160],"Men's Relaxed Fit Straight Jeans"],
  ['jeans',[50,60,120],"Men's Skinny Stretch Jeans"],
  ['jeans',[70,90,150],"Men's Classic Bootcut Jeans"],
  ['jeans',[45,55,110],"Men's Distressed Ripped Jeans"],
  ['jeans',[60,85,145],"Men's Tapered Light Wash Jeans"],
  ['jeans',[35,50,95], "Men's Multi-Pocket Cargo Jeans"],
  ['jeans',[55,75,135],"Men's Jogger-Style Denim Jeans"],
  ['jeans',[30,45,90], "Men's Raw Selvedge Straight Jeans"],
  ['jeans',[65,80,140],"Men's Wide Leg Denim Jeans"],
  // Men's T-Shirts (39-48)
  ['tshirt',[220,220,220],"Men's Classic Crew Neck T-Shirt"],
  ['tshirt',[30,30,30],  "Men's Graphic Print T-Shirt"],
  ['tshirt',[60,100,160],"Men's V-Neck Slim T-Shirt"],
  ['tshirt',[100,160,80],"Men's Pocket Chest T-Shirt"],
  ['tshirt',[180,80,50], "Men's Long Sleeve T-Shirt"],
  ['tshirt',[80,50,140], "Men's Henley Button T-Shirt"],
  ['tshirt',[160,160,200],"Men's Breton Stripe T-Shirt"],
  ['tshirt',[60,160,140],"Men's Performance Dry-Fit T-Shirt"],
  ['tshirt',[200,150,50],"Men's Tie-Dye Oversized T-Shirt"],
  ['tshirt',[50,80,50],  "Men's Longline Extended T-Shirt"],
  // Men's Shirts (49-58)
  ['shirt',[200,210,230],"Men's Classic Oxford Button Shirt"],
  ['shirt',[220,200,150],"Men's Linen Summer Shirt"],
  ['shirt',[130,70,50],  "Men's Flannel Check Shirt"],
  ['shirt',[240,240,245],"Men's Slim Fit Formal Shirt"],
  ['shirt',[60,90,150],  "Men's Denim Western Shirt"],
  ['shirt',[200,150,90], "Men's Hawaiian Camp Collar Shirt"],
  ['shirt',[150,170,190],"Men's Chambray Work Shirt"],
  ['shirt',[90,50,120],  "Men's Dobby Texture Formal Shirt"],
  ['shirt',[60,130,90],  "Men's Mandarin Collar Linen Shirt"],
  ['shirt',[170,100,60], "Men's Twill Overshirt Jacket"],
  // Men's Jackets (59-68)
  ['jacket',[30,30,30],  "Men's Genuine Leather Biker Jacket"],
  ['jacket',[60,90,130], "Men's Quilted Puffer Jacket"],
  ['jacket',[170,120,50],"Men's Lightweight Bomber Jacket"],
  ['jacket',[50,50,80],  "Men's Wool-Cashmere Overcoat"],
  ['jacket',[80,70,50],  "Men's Waxed Cotton Field Jacket"],
  ['jacket',[100,140,180],"Men's Lightweight Windbreaker"],
  ['jacket',[70,100,70], "Men's Softshell Fleece Jacket"],
  ['jacket',[50,50,130], "Men's Varsity College Jacket"],
  ['jacket',[60,55,45],  "Men's Military Cargo Jacket"],
  ['jacket',[55,80,140], "Men's Denim Trucker Jacket"],
  // Men's Shoes (69-78)
  ['shoes',[240,240,240],"Men's Clean White Leather Sneakers"],
  ['shoes',[80,55,35],   "Men's Suede Chelsea Boots"],
  ['shoes',[50,180,120], "Men's Road Running Trainers"],
  ['shoes',[30,30,30],   "Men's Oxford Dress Shoes"],
  ['shoes',[50,50,50],   "Men's Canvas High-Top Sneakers"],
  ['shoes',[160,120,70], "Men's Leather Penny Loafers"],
  ['shoes',[100,80,50],  "Men's Waterproof Hiking Boots"],
  ['shoes',[200,170,120],"Men's Jute Canvas Espadrilles"],
  ['shoes',[60,40,30],   "Men's Wingtip Derby Brogues"],
  ['shoes',[40,40,40],   "Men's Chunky Platform Sneakers"],
  // Kids' T-Shirts (79-88)
  ['tshirt',[255,120,40],"Kids' Dinosaur Print T-Shirt"],
  ['tshirt',[50,80,200], "Kids' Superhero Graphic Tee"],
  ['tshirt',[240,100,150],"Kids' Rainbow Stripe T-Shirt"],
  ['tshirt',[20,20,60],  "Kids' Space Explorer T-Shirt"],
  ['tshirt',[80,180,80], "Kids' Animal Print T-Shirt"],
  ['tshirt',[200,80,180],"Kids' Tie-Dye Effect T-Shirt"],
  ['tshirt',[255,200,30],"Kids' Sports Number T-Shirt"],
  ['tshirt',[170,170,200],"Kids' Plain Essential T-Shirt"],
  ['tshirt',[100,50,150],"Kids' Long Sleeve T-Shirt"],
  ['tshirt',[60,140,200],"Kids' Pocket T-Shirt"],
  // Kids' Jeans (89-98)
  ['jeans',[50,80,160],  "Kids' Slim Fit Dark Wash Jeans"],
  ['jeans',[80,110,180], "Kids' Elasticated Waist Jeans"],
  ['jeans',[60,90,150],  "Kids' Ripped Knee Jeans"],
  ['jeans',[40,60,120],  "Kids' Skinny Stretch Jeans"],
  ['jeans',[70,95,160],  "Kids' Jogger Denim Jeans"],
  ['jeans',[90,115,170], "Kids' Light Wash Straight Jeans"],
  ['jeans',[50,75,135],  "Kids' Cargo Denim Jeans"],
  ['jeans',[65,85,145],  "Kids' Pull-On Jeggings"],
  ['jeans',[45,65,125],  "Kids' Dungaree Denim Overalls"],
  ['jeans',[35,55,110],  "Kids' Wide Leg Barrel Jeans"],
  // Kids' Shorts (99-108)
  ['shorts',[30,120,200],"Kids' Jersey Sport Shorts"],
  ['shorts',[80,110,175],"Kids' Denim Cutoff Shorts"],
  ['shorts',[60,160,220],"Kids' Quick-Dry Swim Shorts"],
  ['shorts',[60,80,50],  "Kids' Cargo Shorts"],
  ['shorts',[240,120,160],"Kids' Floral Print Shorts"],
  ['shorts',[200,160,50],"Kids' Chino Shorts"],
  ['shorts',[80,60,150], "Kids' Cycling Shorts"],
  ['shorts',[60,190,140],"Kids' Tie-Dye Jersey Shorts"],
  ['shorts',[200,200,180],"Kids' Linen Blend Shorts"],
  ['shorts',[150,60,60], "Kids' Roll-Hem Jersey Shorts"],
  // Kids' Hoodies (109-118)
  ['hoodie',[200,100,180],"Kids' Rainbow Zip-Up Hoodie"],
  ['hoodie',[60,80,50],   "Kids' Camo Pullover Hoodie"],
  ['hoodie',[50,100,200], "Kids' Character Print Hoodie"],
  ['hoodie',[170,170,200],"Kids' Fleece-Lined Pullover Hoodie"],
  ['hoodie',[230,140,60], "Kids' Tie-Dye Pullover Hoodie"],
  ['hoodie',[80,50,130],  "Kids' Galaxy Print Hoodie"],
  ['hoodie',[200,60,60],  "Kids' Varsity Logo Hoodie"],
  ['hoodie',[40,120,160], "Kids' Windproof Outdoor Hoodie"],
  ['hoodie',[100,170,100],"Kids' Striped Pullover Hoodie"],
  ['hoodie',[220,200,160],"Kids' Sherpa Fleece Hoodie"],
  // Kids' Shoes (119-128)
  ['shoes',[240,240,240],"Kids' Canvas Lace-Up Sneakers"],
  ['shoes',[50,130,220], "Kids' Velcro Strap Trainers"],
  ['shoes',[200,50,50],  "Kids' Wellington Rain Boots"],
  ['shoes',[30,30,30],   "Kids' Leather School Shoes"],
  ['shoes',[60,200,120], "Kids' Sport Running Shoes"],
  ['shoes',[255,180,50], "Kids' Light-Up LED Trainers"],
  ['shoes',[50,50,50],   "Kids' Chelsea Boots"],
  ['shoes',[220,190,140],"Kids' Slip-On Summer Sandals"],
  ['shoes',[100,60,30],  "Kids' Winter Snow Boots"],
  ['shoes',[170,100,200],"Kids' Glitter High-Top Sneakers"],
];

// ── Resolve the public://products/ directory ─────────────────────────────────
$real_dir = \Drupal::service('file_system')->realpath('public://products/');

echo "\nRegenerating " . count($catalog) . " product images in:\n$real_dir\n\n";

$idx = 29;
foreach ($catalog as [$type, $rgb, $label]) {
  $filepath = "$real_dir/product-$idx.png";
  render_image($type, $rgb, $label, $filepath);
  $idx++;
}

// ── Flush Drupal image-style derivatives so they are rebuilt on next view ────
echo "\nFlushing image style derivatives...\n";
\Drupal::service('image.factory'); // ensure loaded
$styles = \Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple();
foreach ($styles as $style) {
  $style->flush();
  echo "  flushed style: " . $style->id() . "\n";
}

echo "\n✓ Done. Run: ddev drush cr\n";
