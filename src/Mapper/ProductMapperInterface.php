<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Mapper;

use Sylius\Component\Core\Model\ProductInterface;

interface ProductMapperInterface
{
    /** @return array<string, mixed> Brevo product payload */
    public function map(ProductInterface $product, string $locale, string $channelCode): array;
}
