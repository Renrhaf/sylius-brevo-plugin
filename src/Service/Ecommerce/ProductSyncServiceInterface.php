<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Ecommerce;

use Sylius\Component\Core\Model\ProductInterface;

interface ProductSyncServiceInterface
{
    public function syncProduct(ProductInterface $product): void;

    /** @return array{processed: int, failed: int} */
    public function syncAll(): array;
}
