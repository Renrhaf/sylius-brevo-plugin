<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Ecommerce;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;
use Renrhaf\SyliusBrevoPlugin\Entity\SyncLog;
use Renrhaf\SyliusBrevoPlugin\Mapper\ProductMapperInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;

final class ProductSyncService implements ProductSyncServiceInterface
{
    public function __construct(
        private readonly BrevoClientInterface $brevoClient,
        private readonly ProductMapperInterface $productMapper,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly string $defaultLocale,
        private readonly string $defaultChannelCode,
        private readonly int $batchSize,
    ) {
    }

    public function syncProduct(ProductInterface $product): void
    {
        $payload = $this->productMapper->map($product, $this->defaultLocale, $this->defaultChannelCode);
        $this->brevoClient->createOrUpdateProduct($payload);
        $this->logger->info('Product synced to Brevo', ['code' => $product->getCode()]);
    }

    public function syncAll(): array
    {
        $syncLog = new SyncLog(SyncLog::TYPE_PRODUCTS);
        $this->entityManager->persist($syncLog);
        $this->entityManager->flush();

        $processed = 0;
        $failed = 0;

        try {
            $products = $this->productRepository->findBy(['enabled' => true]);
            $batch = [];

            foreach ($products as $product) {
                if (!$product instanceof ProductInterface) {
                    continue;
                }

                try {
                    $mapped = $this->productMapper->map($product, $this->defaultLocale, $this->defaultChannelCode);

                    // Skip products missing required Brevo fields
                    if ('' === ($mapped['name'] ?? '') || '' === ($mapped['url'] ?? '') || '' === ($mapped['imageUrl'] ?? '')) {
                        ++$failed;
                        $syncLog->incrementFailed();
                        $this->logger->debug('Skipping product with missing fields', ['code' => $product->getCode()]);

                        continue;
                    }

                    $batch[] = $mapped;
                } catch (\Throwable $e) {
                    ++$failed;
                    $syncLog->incrementFailed();
                    $this->logger->warning('Failed to map product', [
                        'code' => $product->getCode(),
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }

                if (\count($batch) >= $this->batchSize) {
                    try {
                        $this->brevoClient->batchCreateOrUpdateProducts($batch);
                        $processed += \count($batch);
                        $syncLog->incrementProcessed(\count($batch));
                    } catch (\Throwable $e) {
                        $failed += \count($batch);
                        $syncLog->incrementFailed(\count($batch));
                        $this->logger->warning('Batch product sync failed', ['error' => $e->getMessage()]);
                    }
                    $batch = [];
                }
            }

            // Flush remaining batch
            if ([] !== $batch) {
                try {
                    $this->brevoClient->batchCreateOrUpdateProducts($batch);
                    $processed += \count($batch);
                    $syncLog->incrementProcessed(\count($batch));
                } catch (\Throwable $e) {
                    $failed += \count($batch);
                    $syncLog->incrementFailed(\count($batch));
                    $this->logger->warning('Final batch product sync failed', ['error' => $e->getMessage()]);
                }
            }

            $syncLog->markCompleted();
        } finally {
            $this->entityManager->flush();
        }

        return ['processed' => $processed, 'failed' => $failed];
    }
}
