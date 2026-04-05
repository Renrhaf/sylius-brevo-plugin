<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Mapper;

use Sylius\Component\Core\Model\OrderInterface;

interface OrderMapperInterface
{
    /** @return array<string, mixed> Brevo order payload */
    public function map(OrderInterface $order): array;
}
