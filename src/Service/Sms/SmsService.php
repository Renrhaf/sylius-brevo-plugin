<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Sms;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;

final class SmsService implements SmsServiceInterface
{
    public function __construct(
        private readonly BrevoClientInterface $brevoClient,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
        private readonly ?string $sender,
        private readonly array $events,
    ) {
    }

    public function sendOrderConfirmation(string $phoneNumber, string $orderNumber, string $orderTotal): void
    {
        $this->send('order_confirmation', $phoneNumber, [
            '{ORDER_NUMBER}' => $orderNumber,
            '{ORDER_TOTAL}' => $orderTotal,
        ]);
    }

    public function sendOrderShipped(string $phoneNumber, string $orderNumber): void
    {
        $this->send('order_shipped', $phoneNumber, [
            '{ORDER_NUMBER}' => $orderNumber,
        ]);
    }

    private function send(string $eventType, string $phoneNumber, array $replacements): void
    {
        if (!$this->enabled || null === $this->sender) {
            return;
        }

        $eventConfig = $this->events[$eventType] ?? null;

        if (null === $eventConfig || !($eventConfig['enabled'] ?? false)) {
            return;
        }

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $eventConfig['content'] ?? '',
        );

        if ('' === $content) {
            return;
        }

        // Normalize phone number: ensure it starts with country code
        $recipient = $this->normalizePhone($phoneNumber);

        if ('' === $recipient) {
            return;
        }

        try {
            $this->brevoClient->sendTransactionalSms($this->sender, $recipient, $content);

            $this->logger->info('Brevo SMS sent', [
                'type' => $eventType,
                'recipient' => $recipient,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send Brevo SMS', [
                'type' => $eventType,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizePhone(string $phone): string
    {
        // Remove spaces, dashes, dots
        $phone = preg_replace('/[\s\-\.]/', '', $phone) ?? '';

        // Already has international prefix
        if (str_starts_with($phone, '+') || str_starts_with($phone, '00')) {
            return $phone;
        }

        // French number starting with 0 → add +33
        if (str_starts_with($phone, '0') && \strlen($phone) === 10) {
            return '+33' . substr($phone, 1);
        }

        return $phone;
    }
}
