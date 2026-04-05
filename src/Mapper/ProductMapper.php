<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Mapper;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProductMapper implements ProductMapperInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $baseUrl = '',
    ) {
    }

    public function map(ProductInterface $product, string $locale, string $channelCode): array
    {
        $translation = $product->getTranslation($locale);

        // Get the first enabled variant's price
        $price = 0.0;
        $sku = $product->getCode() ?? '';

        foreach ($product->getVariants() as $variant) {
            if (!$variant instanceof ProductVariantInterface) {
                continue;
            }

            foreach ($variant->getChannelPricings() as $pricing) {
                if ($pricing->getChannelCode() === $channelCode && null !== $pricing->getPrice()) {
                    $price = $pricing->getPrice() / 100;
                    $sku = $variant->getCode() ?? $sku;

                    break 2;
                }
            }
        }

        // Build product URL
        $slug = $translation->getSlug();
        $url = '';

        if (null !== $slug && '' !== $slug) {
            try {
                $url = $this->urlGenerator->generate(
                    'sylius_shop_product_show',
                    ['slug' => $slug, '_locale' => $locale],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                );
            } catch (\Throwable) {
                // Fallback: build URL manually when outside HTTP context (CLI)
                if ('' !== $this->baseUrl) {
                    $url = rtrim($this->baseUrl, '/') . '/' . $locale . '/products/' . $slug;
                }
            }
        }

        // Get image URL
        $imageUrl = '';
        $firstImage = $product->getImages()->first();

        if (false !== $firstImage && null !== $firstImage->getPath()) {
            $path = $firstImage->getPath();
            // Build absolute URL for image
            if ('' !== $this->baseUrl && !str_starts_with($path, 'http')) {
                $imageUrl = rtrim($this->baseUrl, '/') . '/media/image/' . ltrim($path, '/');
            } else {
                $imageUrl = $path;
            }
        }

        // Get category IDs
        $categories = [];

        foreach ($product->getTaxons() as $taxon) {
            $categories[] = (string) $taxon->getCode();
        }

        return [
            'id' => $product->getCode(),
            'name' => $translation->getName() ?? '',
            'url' => $url,
            'imageUrl' => $imageUrl,
            'sku' => $sku,
            'price' => $price,
            'categories' => $categories,
            'description' => mb_substr(strip_tags($translation->getDescription() ?? ''), 0, 2500),
            'metaInfo' => [
                'slug' => $translation->getSlug(),
            ],
            'updateEnabled' => true,
        ];
    }
}
