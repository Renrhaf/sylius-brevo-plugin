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
        private readonly ?string $brandAttribute = null,
    ) {
    }

    public function map(ProductInterface $product, string $locale, string $channelCode): array
    {
        $translation = $product->getTranslation($locale);
        $productCode = $product->getCode() ?? '';

        // Build product URL
        $url = $this->buildProductUrl($translation->getSlug(), $locale);

        // Get image URL
        $imageUrl = $this->buildImageUrl($product);

        // Get category IDs
        $categories = [];

        foreach ($product->getTaxons() as $taxon) {
            $categories[] = (string) $taxon->getCode();
        }

        $description = mb_substr(strip_tags($translation->getDescription() ?? ''), 0, 2500);

        // Build parent product
        $variants = $product->getVariants();
        $firstVariant = $variants->first();
        $firstPrice = 0.0;

        if ($firstVariant instanceof ProductVariantInterface) {
            $firstPrice = $this->getVariantPrice($firstVariant, $channelCode);
        }

        $parent = [
            'id' => $productCode,
            'name' => $translation->getName() ?? '',
            'url' => $url,
            'imageUrl' => $imageUrl,
            'sku' => $productCode,
            'price' => $firstPrice,
            'categories' => $categories,
            'description' => $description,
            'metaInfo' => ['slug' => $translation->getSlug()],
            'updateEnabled' => true,
        ];

        $brand = $this->getProductBrand($product, $locale);

        if (null !== $brand) {
            $parent['brand'] = $brand;
        }

        $results = [$parent];

        // If product has multiple variants, add each as a child product
        if ($variants->count() > 1) {
            foreach ($variants as $variant) {
                if (!$variant instanceof ProductVariantInterface) {
                    continue;
                }

                $variantCode = $variant->getCode();

                if (null === $variantCode || $variantCode === $productCode) {
                    continue;
                }

                $variantTranslation = $variant->getTranslation($locale);
                $variantName = $variantTranslation->getName();
                $displayName = $translation->getName() ?? '';

                if (null !== $variantName && '' !== $variantName) {
                    $displayName .= ' — ' . $variantName;
                }

                $results[] = [
                    'id' => $variantCode,
                    'name' => $displayName,
                    'url' => $url,
                    'imageUrl' => $imageUrl,
                    'sku' => $variantCode,
                    'price' => $this->getVariantPrice($variant, $channelCode),
                    'categories' => $categories,
                    'description' => $description,
                    'parentId' => $productCode,
                    'metaInfo' => ['variant' => true],
                    'updateEnabled' => true,
                ];
            }
        }

        return $results;
    }

    private function getVariantPrice(ProductVariantInterface $variant, string $channelCode): float
    {
        foreach ($variant->getChannelPricings() as $pricing) {
            if ($pricing->getChannelCode() === $channelCode && null !== $pricing->getPrice()) {
                return $pricing->getPrice() / 100;
            }
        }

        return 0.0;
    }

    private function buildProductUrl(?string $slug, string $locale): string
    {
        if (null === $slug || '' === $slug) {
            return '';
        }

        try {
            return $this->urlGenerator->generate(
                'sylius_shop_product_show',
                ['slug' => $slug, '_locale' => $locale],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        } catch (\Throwable) {
            if ('' !== $this->baseUrl) {
                return rtrim($this->baseUrl, '/') . '/' . $locale . '/products/' . $slug;
            }

            return '';
        }
    }

    private function buildImageUrl(ProductInterface $product): string
    {
        $firstImage = $product->getImages()->first();

        if (false === $firstImage || null === $firstImage->getPath()) {
            return '';
        }

        $path = $firstImage->getPath();

        if ('' !== $this->baseUrl && !str_starts_with($path, 'http')) {
            return rtrim($this->baseUrl, '/') . '/media/image/' . ltrim($path, '/');
        }

        return $path;
    }

    private function getProductBrand(ProductInterface $product, string $locale): ?string
    {
        if (null === $this->brandAttribute || '' === $this->brandAttribute) {
            return null;
        }

        foreach ($product->getAttributes() as $attributeValue) {
            if ($attributeValue->getLocaleCode() !== $locale) {
                continue;
            }

            $attribute = $attributeValue->getAttribute();

            if (null !== $attribute && $attribute->getCode() === $this->brandAttribute) {
                $value = $attributeValue->getValue();

                return \is_string($value) && '' !== $value ? $value : null;
            }
        }

        return null;
    }
}
