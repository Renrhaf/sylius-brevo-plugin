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
    ) {
    }

    public function map(ProductInterface $product, string $locale, string $channelCode): array
    {
        $translation = $product->getTranslation($locale);

        // Get the first variant's price
        $price = 0.0;
        $sku = $product->getCode() ?? '';
        $firstVariant = $product->getVariants()->first();

        if ($firstVariant instanceof ProductVariantInterface) {
            $channelPricing = $firstVariant->getChannelPricingForChannel(
                $product->getChannels()->first(),
            );

            if (null !== $channelPricing) {
                $price = $channelPricing->getPrice() / 100;
            }

            $sku = $firstVariant->getCode() ?? $sku;
        }

        // Build product URL
        $url = '';

        try {
            $url = $this->urlGenerator->generate(
                'sylius_shop_product_show',
                ['slug' => $translation->getSlug(), '_locale' => $locale],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        } catch (\Throwable) {
            // URL generation may fail outside request context
        }

        // Get image URL
        $imageUrl = '';
        $firstImage = $product->getImages()->first();

        if (false !== $firstImage) {
            $imageUrl = $firstImage->getPath() ?? '';
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
            'description' => mb_substr($translation->getDescription() ?? '', 0, 3000),
            'metaInfo' => [
                'slug' => $translation->getSlug(),
            ],
            'updateEnabled' => true,
        ];
    }
}
