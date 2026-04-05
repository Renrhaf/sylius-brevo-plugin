<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\EventListener;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Service\Newsletter\NewsletterServiceInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Subscribes customer to newsletter when they opt in during checkout.
 */
final class CheckoutNewsletterListener
{
    public function __construct(
        private readonly NewsletterServiceInterface $newsletterService,
        private readonly LoggerInterface $logger,
        private readonly bool $enabled,
    ) {
    }

    #[AsEventListener(event: 'sylius.order.post_complete')]
    public function onOrderComplete(GenericEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $order = $event->getSubject();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $customer = $order->getCustomer();

        if (!$customer instanceof CustomerInterface) {
            return;
        }

        if (!$customer->isSubscribedToNewsletter()) {
            return;
        }

        try {
            $this->newsletterService->subscribe(
                $customer->getEmail(),
                $customer->getFirstName() ?? '',
                $customer->getLastName() ?? '',
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to subscribe customer to Brevo newsletter after checkout', [
                'email' => $customer->getEmail(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
