<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Ecommerce;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;
use Renrhaf\SyliusBrevoPlugin\Entity\SyncLog;
use Renrhaf\SyliusBrevoPlugin\Mapper\CategoryMapperInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

final class CategorySyncService implements CategorySyncServiceInterface
{
    public function __construct(
        private readonly BrevoClientInterface $brevoClient,
        private readonly CategoryMapperInterface $categoryMapper,
        private readonly TaxonRepositoryInterface $taxonRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly string $defaultLocale,
    ) {
    }

    public function syncAll(): array
    {
        $syncLog = new SyncLog(SyncLog::TYPE_CATEGORIES);
        $this->entityManager->persist($syncLog);
        $this->entityManager->flush();

        $processed = 0;
        $failed = 0;

        try {
            $taxons = $this->taxonRepository->findAll();
            $batch = [];

            foreach ($taxons as $taxon) {
                if (!$taxon instanceof TaxonInterface) {
                    continue;
                }

                try {
                    $batch[] = $this->categoryMapper->map($taxon, $this->defaultLocale);

                    if (\count($batch) >= 100) {
                        $this->brevoClient->batchCreateOrUpdateCategories($batch);
                        $processed += \count($batch);
                        $syncLog->incrementProcessed(\count($batch));
                        $batch = [];
                    }
                } catch (\Throwable $e) {
                    ++$failed;
                    $syncLog->incrementFailed();
                    $this->logger->warning('Failed to map category', [
                        'code' => $taxon->getCode(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ([] !== $batch) {
                $this->brevoClient->batchCreateOrUpdateCategories($batch);
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
