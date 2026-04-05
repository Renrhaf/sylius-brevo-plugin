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
                    [$p, $f] = $this->sendBatch($batch, $syncLog);
                    $processed += $p;
                    $failed += $f;
                    $batch = [];
                }
            }

            // Flush remaining batch
            if ([] !== $batch) {
                [$p, $f] = $this->sendBatch($batch, $syncLog);
                $processed += $p;
                $failed += $f;
            }

            $syncLog->markCompleted();
        } finally {
            $this->entityManager->flush();
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    /**
     * Sends a batch. On failure, falls back to individual product sync.
     *
     * @param array<int, array<string, mixed>> $batch
     *
     * @return array{0: int, 1: int} [processed, failed]
     */
    private function sendBatch(array $batch, SyncLog $syncLog): array
    {
        try {
            $this->brevoClient->batchCreateOrUpdateProducts($batch);
            $syncLog->incrementProcessed(\count($batch));

            return [\count($batch), 0];
        } catch (\Throwable $batchError) {
            $this->logger->info('Batch failed, falling back to individual sync', [
                'batch_size' => \count($batch),
                'error' => $batchError->getMessage(),
            ]);
        }

        // Fallback: sync one by one
        $processed = 0;
        $failed = 0;

        foreach ($batch as $product) {
            try {
                $this->brevoClient->createOrUpdateProduct($product);
                ++$processed;
                $syncLog->incrementProcessed();
            } catch (\Throwable $e) {
                ++$failed;
                $syncLog->incrementFailed();
                $this->logger->debug('Product sync failed', [
                    'id' => $product['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [$processed, $failed];
    }
}
