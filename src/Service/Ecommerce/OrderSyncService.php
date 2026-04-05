<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Ecommerce;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;
use Renrhaf\SyliusBrevoPlugin\Entity\SyncLog;
use Renrhaf\SyliusBrevoPlugin\Mapper\OrderMapperInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

final class OrderSyncService implements OrderSyncServiceInterface
{
    /** @param array<int, string> $syncStatuses */
    public function __construct(
        private readonly BrevoClientInterface $brevoClient,
        private readonly OrderMapperInterface $orderMapper,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly array $syncStatuses,
    ) {
    }

    public function syncOrder(OrderInterface $order): void
    {
        if (null === $order->getCustomer()) {
            $this->logger->debug('Skipping order without customer', ['number' => $order->getNumber()]);

            return;
        }

        $payload = $this->orderMapper->map($order);
        $this->brevoClient->createOrUpdateOrder($payload);
        $this->logger->info('Order synced to Brevo', ['number' => $order->getNumber()]);
    }

    public function syncAll(?\DateTimeInterface $since = null): array
    {
        $syncLog = new SyncLog(SyncLog::TYPE_ORDERS);
        $this->entityManager->persist($syncLog);
        $this->entityManager->flush();

        $processed = 0;
        $failed = 0;

        try {
            $qb = $this->orderRepository->createQueryBuilder('o')
                ->andWhere('o.state != :cart')
                ->andWhere('o.customer IS NOT NULL')
                ->setParameter('cart', OrderInterface::STATE_CART);

            if (null !== $since) {
                $qb->andWhere('o.updatedAt >= :since')
                    ->setParameter('since', $since);
            }

            $orders = $qb->getQuery()->getResult();

            $batch = [];

            foreach ($orders as $order) {
                if (!$order instanceof OrderInterface) {
                    continue;
                }

                try {
                    $batch[] = $this->orderMapper->map($order);

                    if (\count($batch) >= 500) {
                        $this->brevoClient->batchCreateOrUpdateOrders($batch);
                        $processed += \count($batch);
                        $syncLog->incrementProcessed(\count($batch));
                        $batch = [];
                    }
                } catch (\Throwable $e) {
                    ++$failed;
                    $syncLog->incrementFailed();
                    $this->logger->warning('Failed to map order', [
                        'number' => $order->getNumber(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ([] !== $batch) {
                $this->brevoClient->batchCreateOrUpdateOrders($batch);
                $processed += \count($batch);
                $syncLog->incrementProcessed(\count($batch));
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
