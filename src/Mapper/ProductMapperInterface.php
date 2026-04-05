<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Mapper;

use Sylius\Component\Core\Model\ProductInterface;

interface ProductMapperInterface
{
    /**
     * Maps a Sylius product to one or more Brevo product payloads.
     * Returns the parent product + one entry per variant (linked via parentId).
     *
     * @return array<int, array<string, mixed>> List of Brevo product payloads
     */
    public function map(ProductInterface $product, string $locale, string $channelCode): array;
}
