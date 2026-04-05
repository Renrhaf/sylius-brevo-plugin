<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\MessageHandler;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Message\SyncContactMessage;
use Renrhaf\SyliusBrevoPlugin\Service\Contact\ContactSyncServiceInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncContactHandler
{
    public function __construct(
        private ContactSyncServiceInterface $contactSyncService,
        private CustomerRepositoryInterface $customerRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SyncContactMessage $message): void
    {
        $customer = $this->customerRepository->find($message->customerId);

        if (!$customer instanceof CustomerInterface) {
            $this->logger->debug('Customer not found for Brevo sync', ['id' => $message->customerId]);

            return;
        }

        $this->contactSyncService->syncCustomer($customer);
    }
}
