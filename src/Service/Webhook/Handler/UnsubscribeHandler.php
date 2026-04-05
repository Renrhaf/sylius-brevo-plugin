<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Webhook\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Service\Webhook\WebhookHandlerInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;

/**
 * Handles unsubscribe events from Brevo — updates customer newsletter status in Sylius.
 */
final class UnsubscribeHandler implements WebhookHandlerInterface
{
    private static bool $syncEnabled = true;

    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function disableSync(): void
    {
        self::$syncEnabled = false;
    }

    public static function enableSync(): void
    {
        self::$syncEnabled = true;
    }

    public static function isSyncEnabled(): bool
    {
        return self::$syncEnabled;
    }

    public function supports(string $eventType): bool
    {
        return \in_array($eventType, ['unsubscribe', 'unsubscribed'], true);
    }

    public function handle(array $payload): void
    {
        $email = $payload['email'] ?? '';

        if ('' === $email) {
            return;
        }

        $customer = $this->customerRepository->findOneBy(['email' => $email]);

        if (!$customer instanceof CustomerInterface) {
            $this->logger->debug('No customer found for unsubscribe webhook', ['email' => $email]);

            return;
        }

        if (!$customer->isSubscribedToNewsletter()) {
            return;
        }

        self::disableSync();

        try {
            $customer->setSubscribedToNewsletter(false);
            $this->entityManager->flush();
            $this->logger->info('Customer unsubscribed via Brevo webhook', ['email' => $email]);
        } finally {
            self::enableSync();
        }
    }
}
