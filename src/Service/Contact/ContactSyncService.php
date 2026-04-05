<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Contact;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;
use Renrhaf\SyliusBrevoPlugin\Entity\SyncLog;
use Renrhaf\SyliusBrevoPlugin\Mapper\ContactMapperInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;

final class ContactSyncService implements ContactSyncServiceInterface
{
    public function __construct(
        private readonly BrevoClientInterface $brevoClient,
        private readonly ContactMapperInterface $contactMapper,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly int $listId,
    ) {
    }

    public function syncCustomer(CustomerInterface $customer): void
    {
        $email = $customer->getEmail();

        if ('' === $email || null === $email) {
            return;
        }

        $attributes = $this->contactMapper->map($customer);

        $listIds = $customer->isSubscribedToNewsletter() ? [$this->listId] : [];

        $this->brevoClient->createContact($email, $attributes, $listIds);

        $this->logger->info('Customer synced to Brevo', ['email' => $email]);
    }

    public function syncAll(): array
    {
        $syncLog = new SyncLog(SyncLog::TYPE_CONTACTS);
        $this->entityManager->persist($syncLog);
        $this->entityManager->flush();

        $processed = 0;
        $failed = 0;

        try {
            $customers = $this->customerRepository->findAll();

            foreach ($customers as $customer) {
                if (!$customer instanceof CustomerInterface) {
                    continue;
                }

                try {
                    $this->syncCustomer($customer);
                    ++$processed;
                    $syncLog->incrementProcessed();
                } catch (\Throwable $e) {
                    ++$failed;
                    $syncLog->incrementFailed();
                    $this->logger->warning('Failed to sync customer', [
                        'email' => $customer->getEmail(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $syncLog->markCompleted();
        } catch (\Throwable $e) {
            $syncLog->markFailed($e->getMessage());

            throw $e;
        } finally {
            $this->entityManager->flush();
        }

        return ['processed' => $processed, 'failed' => $failed];
    }
}
