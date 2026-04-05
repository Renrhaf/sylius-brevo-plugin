<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\EventListener;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Service\Ecommerce\CartTrackingServiceInterface;
use Renrhaf\SyliusBrevoPlugin\Service\Ecommerce\OrderSyncServiceInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Listens to Sylius order events to sync orders and track carts in Brevo.
 */
final class OrderSyncListener
{
    public function __construct(
        private readonly OrderSyncServiceInterface $orderSyncService,
        private readonly CartTrackingServiceInterface $cartTrackingService,
        private readonly LoggerInterface $logger,
        private readonly bool $orderSyncEnabled,
        private readonly bool $cartTrackingEnabled,
    ) {
    }

    #[AsEventListener(event: 'sylius.order.post_complete')]
    public function onOrderComplete(GenericEvent $event): void
    {
        $order = $event->getSubject();

        if (!$order instanceof OrderInterface) {
            return;
        }

        if ($this->orderSyncEnabled) {
            try {
                $this->orderSyncService->syncOrder($order);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to sync order to Brevo', [
                    'order' => $order->getNumber(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->cartTrackingEnabled) {
            try {
                $this->cartTrackingService->trackOrderCompleted($order);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to track order completion in Brevo', [
                    'order' => $order->getNumber(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
