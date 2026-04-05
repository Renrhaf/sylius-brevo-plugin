<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\EventListener;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Message\SyncOrderMessage;
use Renrhaf\SyliusBrevoPlugin\Service\Ecommerce\CartTrackingServiceInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Dispatches async order sync messages to Brevo on order completion.
 * Cart tracking remains synchronous (event-driven).
 */
final class OrderSyncListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
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
            $this->messageBus->dispatch(new SyncOrderMessage($order->getId()));
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
