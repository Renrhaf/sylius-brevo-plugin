<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Ecommerce;

use Sylius\Component\Core\Model\OrderInterface;

interface OrderSyncServiceInterface
{
    public function syncOrder(OrderInterface $order): void;

    /** @return array{processed: int, failed: int} */
    public function syncAll(\DateTimeInterface $since = null): array;
}
