<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\MessageHandler;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Message\SyncProductMessage;
use Renrhaf\SyliusBrevoPlugin\Service\Ecommerce\ProductSyncServiceInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncProductHandler
{
    public function __construct(
        private ProductSyncServiceInterface $productSyncService,
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SyncProductMessage $message): void
    {
        $product = $this->productRepository->find($message->productId);

        if (!$product instanceof ProductInterface) {
            $this->logger->debug('Product not found for Brevo sync', ['id' => $message->productId]);

            return;
        }

        if (!$product->isEnabled()) {
            $this->logger->debug('Skipping disabled product for Brevo sync', ['code' => $product->getCode()]);

            return;
        }

        $this->productSyncService->syncProduct($product);
    }
}
