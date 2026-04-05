<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Ecommerce;

use Sylius\Component\Core\Model\OrderInterface;

interface CartTrackingServiceInterface
{
    public function trackCartUpdated(OrderInterface $cart): void;

    public function trackCartDeleted(OrderInterface $cart): void;

    public function trackOrderCompleted(OrderInterface $order): void;
}
