<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Command;

use Renrhaf\SyliusBrevoPlugin\Service\Ecommerce\ProductSyncServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'renrhaf:brevo:sync-products', description: 'Sync all products to Brevo')]
final class SyncProductsCommand extends Command
{
    public function __construct(
        private readonly ProductSyncServiceInterface $productSyncService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Syncing products to Brevo');

        $result = $this->productSyncService->syncAll();

        $io->success(sprintf('Done: %d processed, %d failed.', $result['processed'], $result['failed']));

        return Command::SUCCESS;
    }
}
