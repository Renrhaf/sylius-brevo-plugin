<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Ecommerce;

interface CategorySyncServiceInterface
{
    /** @return array{processed: int, failed: int} */
    public function syncAll(): array;
}
