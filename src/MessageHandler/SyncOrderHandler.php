<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\MessageHandler;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Message\SyncOrderMessage;
use Renrhaf\SyliusBrevoPlugin\Service\Ecommerce\OrderSyncServiceInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncOrderHandler
{
    public function __construct(
        private OrderSyncServiceInterface $orderSyncService,
        private OrderRepositoryInterface $orderRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SyncOrderMessage $message): void
    {
        $order = $this->orderRepository->find($message->orderId);

        if (!$order instanceof OrderInterface) {
            $this->logger->debug('Order not found for Brevo sync', ['id' => $message->orderId]);

            return;
        }

        $this->orderSyncService->syncOrder($order);
    }
}
