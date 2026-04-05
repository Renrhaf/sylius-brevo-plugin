<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\EventListener;

use Renrhaf\SyliusBrevoPlugin\Message\SyncCategoryMessage;
use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Dispatches async category sync messages to Brevo on taxon create/update.
 */
final class TaxonSyncListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly bool $enabled,
    ) {
    }

    #[AsEventListener(event: 'sylius.taxon.post_create')]
    #[AsEventListener(event: 'sylius.taxon.post_update')]
    public function onTaxonChange(GenericEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $taxon = $event->getSubject();

        if (!$taxon instanceof TaxonInterface) {
            return;
        }

        $this->messageBus->dispatch(new SyncCategoryMessage($taxon->getId()));
    }
}
