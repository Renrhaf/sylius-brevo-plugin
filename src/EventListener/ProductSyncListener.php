<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\EventListener;

use Renrhaf\SyliusBrevoPlugin\Message\SyncProductMessage;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Dispatches async product sync messages to Brevo on product create/update.
 */
final class ProductSyncListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly bool $enabled,
    ) {
    }

    #[AsEventListener(event: 'sylius.product.post_create')]
    #[AsEventListener(event: 'sylius.product.post_update')]
    public function onProductChange(GenericEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $product = $event->getSubject();

        if (!$product instanceof ProductInterface) {
            return;
        }

        $this->messageBus->dispatch(new SyncProductMessage($product->getId()));
    }
}
