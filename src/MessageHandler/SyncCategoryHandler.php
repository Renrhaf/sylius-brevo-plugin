<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\MessageHandler;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;
use Renrhaf\SyliusBrevoPlugin\Mapper\CategoryMapperInterface;
use Renrhaf\SyliusBrevoPlugin\Message\SyncCategoryMessage;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncCategoryHandler
{
    public function __construct(
        private BrevoClientInterface $brevoClient,
        private CategoryMapperInterface $categoryMapper,
        private TaxonRepositoryInterface $taxonRepository,
        private LoggerInterface $logger,
        private string $defaultLocale,
    ) {
    }

    public function __invoke(SyncCategoryMessage $message): void
    {
        $taxon = $this->taxonRepository->find($message->taxonId);

        if (!$taxon instanceof TaxonInterface) {
            $this->logger->debug('Taxon not found for Brevo sync', ['id' => $message->taxonId]);

            return;
        }

        $payload = $this->categoryMapper->map($taxon, $this->defaultLocale);
        $this->brevoClient->createOrUpdateCategory($payload);
        $this->logger->info('Category synced to Brevo', ['code' => $taxon->getCode()]);
    }
}
