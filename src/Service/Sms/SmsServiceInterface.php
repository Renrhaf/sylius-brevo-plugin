<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Sms;

interface SmsServiceInterface
{
    public function sendOrderConfirmation(string $phoneNumber, string $orderNumber, string $orderTotal): void;

    public function sendOrderShipped(string $phoneNumber, string $orderNumber): void;
}
