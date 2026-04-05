<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\EventListener;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Service\Ecommerce\CartTrackingServiceInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tracks cart changes in Brevo for abandoned cart automations.
 */
final class CartTrackingListener
{
    public function __construct(
        private readonly CartTrackingServiceInterface $cartTrackingService,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
    ) {
    }

    #[AsEventListener(event: 'sylius.cart.post_update')]
    #[AsEventListener(event: 'sylius.order_item.post_add')]
    public function onCartUpdate(GenericEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $subject = $event->getSubject();
        $order = $subject instanceof OrderInterface ? $subject : null;

        if (null === $order) {
            return;
        }

        try {
            $this->cartTrackingService->trackCartUpdated($order);
        } catch (\Throwable $e) {
            $this->logger->debug('Failed to track cart update in Brevo', ['error' => $e->getMessage()]);
        }
    }

    #[AsEventListener(event: 'sylius.order_item.post_remove')]
    public function onCartItemRemove(GenericEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $subject = $event->getSubject();
        $order = $subject instanceof OrderInterface ? $subject : null;

        if (null === $order || 0 === $order->getItems()->count()) {
            if (null !== $order) {
                try {
                    $this->cartTrackingService->trackCartDeleted($order);
                } catch (\Throwable $e) {
                    $this->logger->debug('Failed to track cart deletion in Brevo', ['error' => $e->getMessage()]);
                }
            }

            return;
        }

        try {
            $this->cartTrackingService->trackCartUpdated($order);
        } catch (\Throwable $e) {
            $this->logger->debug('Failed to track cart update in Brevo', ['error' => $e->getMessage()]);
        }
    }
}
