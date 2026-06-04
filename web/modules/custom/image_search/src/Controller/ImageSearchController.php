<?php

namespace Drupal\image_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\image_search\Service\ImageHasher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ImageSearchController extends ControllerBase {

  private ImageHasher $hasher;

  public function __construct(ImageHasher $hasher) {
    $this->hasher = $hasher;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('image_search.hasher'),
    );
  }

  public function page(): array {
    return [
      '#theme'    => 'image_search_page',
      '#attached' => ['library' => ['image_search/image-search']],
    ];
  }

  public function search(Request $request): JsonResponse {
    $file = $request->files->get('image');
    if (!$file || !$file->isValid()) {
      return new JsonResponse(['error' => 'No valid image uploaded.'], 400);
    }

    if (!@getimagesize($file->getPathname())) {
      return new JsonResponse(['error' => 'Unsupported image type.'], 400);
    }

    $hits = $this->hasher->search($file->getPathname(), 12);

    if (empty($hits)) {
      return new JsonResponse(['results' => []]);
    }

    $productStorage = $this->entityTypeManager()->getStorage('commerce_product');
    $fileUrlGen     = \Drupal::service('file_url_generator');
    $results        = [];

    foreach ($hits as $hit) {
      $product = $productStorage->load($hit['product_id']);
      if (!$product) continue;

      $price = NULL;
      if (!$product->get('variations')->isEmpty()) {
        $variation = $product->get('variations')->entity;
        if ($variation && !$variation->get('price')->isEmpty()) {
          $priceObj = $variation->get('price')->first()->toPrice();
          $price    = number_format((float) $priceObj->getNumber(), 2) . ' ' . $priceObj->getCurrencyCode();
        }
      }

      $imgUrl = NULL;
      if (!$product->get('field_product_image')->isEmpty()) {
        $imgFile = $product->get('field_product_image')->entity;
        if ($imgFile) {
          $imgUrl = $fileUrlGen->generateAbsoluteString($imgFile->getFileUri());
        }
      }

      $results[] = [
        'product_id' => $hit['product_id'],
        'title'      => $product->getTitle(),
        'score'      => $hit['score'],
        'type'       => $hit['type'] ?? 'similar',
        'price'      => $price,
        'img_url'    => $imgUrl,
        'url'        => $product->toUrl()->toString(),
      ];
    }

    return new JsonResponse(['results' => $results]);
  }
}
