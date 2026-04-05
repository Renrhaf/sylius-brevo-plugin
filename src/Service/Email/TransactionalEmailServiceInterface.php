<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Email;

interface TransactionalEmailServiceInterface
{
    /** @param array<string, mixed> $orderParams */
    public function sendOrderConfirmation(string $email, string $customerName, array $orderParams): void;

    /** @param array<string, mixed> $orderParams */
    public function sendOrderShipped(string $email, string $customerName, array $orderParams): void;

    /** @param array<string, mixed> $params */
    public function sendCustomerWelcome(string $email, string $customerName, array $params = []): void;
}
