<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Mapper;

use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CategoryMapper implements CategoryMapperInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function map(TaxonInterface $taxon, string $locale): array
    {
        $translation = $taxon->getTranslation($locale);

        $url = '';

        try {
            $url = $this->urlGenerator->generate(
                'sylius_shop_product_index',
                ['slug' => $translation->getSlug(), '_locale' => $locale],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        } catch (\Throwable) {
        }

        return [
            'id' => $taxon->getCode(),
            'name' => $translation->getName() ?? '',
            'url' => $url,
            'updateEnabled' => true,
        ];
    }
}
