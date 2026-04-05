<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Command;

use Renrhaf\SyliusBrevoPlugin\Service\Ecommerce\CategorySyncServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'renrhaf:brevo:sync-categories', description: 'Sync categories to Brevo')]
final class SyncCategoriesCommand extends Command
{
    public function __construct(
        private readonly CategorySyncServiceInterface $categorySyncService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Syncing categories to Brevo');

        $result = $this->categorySyncService->syncAll();

        $io->success(sprintf('Done: %d processed, %d failed.', $result['processed'], $result['failed']));

        return Command::SUCCESS;
    }
}
