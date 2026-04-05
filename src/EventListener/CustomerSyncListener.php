<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\EventListener;

use Renrhaf\SyliusBrevoPlugin\Service\Contact\ContactSyncServiceInterface;
use Renrhaf\SyliusBrevoPlugin\Service\Newsletter\NewsletterServiceInterface;
use Renrhaf\SyliusBrevoPlugin\Service\Webhook\Handler\UnsubscribeHandler;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Syncs customer data to Brevo on create/update.
 * Also handles newsletter subscribe/unsubscribe.
 */
final class CustomerSyncListener
{
    public function __construct(
        private readonly ContactSyncServiceInterface $contactSyncService,
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

        $this->contactSyncService->syncCustomer($customer);

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

        $this->contactSyncService->syncCustomer($customer);
    }
}
