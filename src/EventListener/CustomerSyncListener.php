<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\EventListener;

use Renrhaf\SyliusBrevoPlugin\Message\SyncContactMessage;
use Renrhaf\SyliusBrevoPlugin\Service\Newsletter\NewsletterServiceInterface;
use Renrhaf\SyliusBrevoPlugin\Service\Webhook\Handler\UnsubscribeHandler;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Dispatches async contact sync messages to Brevo on customer create/update.
 * Also handles newsletter subscribe/unsubscribe synchronously.
 */
final class CustomerSyncListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly NewsletterServiceInterface $newsletterService,
        private readonly bool $syncOnCreate,
        private readonly bool $syncOnUpdate,
        private readonly bool $newsletterEnabled,
    ) {
    }

    #[AsEventListener(event: 'sylius.customer.post_create')]
    public function onCustomerCreate(GenericEvent $event): void
    {
        if (!$this->syncOnCreate) {
            return;
        }

        $customer = $event->getSubject();

        if (!$customer instanceof CustomerInterface) {
            return;
        }

        $this->messageBus->dispatch(new SyncContactMessage($customer->getId()));

        if ($this->newsletterEnabled && $customer->isSubscribedToNewsletter()) {
            $this->newsletterService->subscribe(
                $customer->getEmail(),
                $customer->getFirstName() ?? '',
                $customer->getLastName() ?? '',
            );
        }
    }

    #[AsEventListener(event: 'sylius.customer.post_update')]
    public function onCustomerUpdate(GenericEvent $event): void
    {
        if (!$this->syncOnUpdate || !UnsubscribeHandler::isSyncEnabled()) {
            return;
        }

        $customer = $event->getSubject();

        if (!$customer instanceof CustomerInterface) {
            return;
        }

        $this->messageBus->dispatch(new SyncContactMessage($customer->getId()));
    }
}
