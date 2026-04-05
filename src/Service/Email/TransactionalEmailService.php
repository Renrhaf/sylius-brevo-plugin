<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Email;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;

final class TransactionalEmailService implements TransactionalEmailServiceInterface
{
    public function __construct(
        private readonly BrevoClientInterface $brevoClient,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
        private readonly ?string $senderName,
        private readonly ?string $senderEmail,
        private readonly array $templates,
    ) {
    }

    public function sendOrderConfirmation(string $email, string $customerName, array $orderParams): void
    {
        $this->send('order_confirmation', $email, $customerName, $orderParams);
    }

    public function sendOrderShipped(string $email, string $customerName, array $orderParams): void
    {
        $this->send('order_shipped', $email, $customerName, $orderParams);
    }

    public function sendCustomerWelcome(string $email, string $customerName, array $params = []): void
    {
        $this->send('customer_welcome', $email, $customerName, $params);
    }

    private function send(string $eventType, string $email, string $name, array $params): void
    {
        if (!$this->enabled) {
            return;
        }

        $templateId = $this->templates[$eventType] ?? null;

        if (null === $templateId) {
            return;
        }

        $sender = null;

        if (null !== $this->senderName && null !== $this->senderEmail) {
            $sender = ['name' => $this->senderName, 'email' => $this->senderEmail];
        }

        try {
            $this->brevoClient->sendTransactionalEmail(
                $templateId,
                [['email' => $email, 'name' => $name]],
                $params,
                $sender,
            );

            $this->logger->info('Brevo transactional email sent', [
                'type' => $eventType,
                'email' => $email,
                'templateId' => $templateId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send Brevo transactional email', [
                'type' => $eventType,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
