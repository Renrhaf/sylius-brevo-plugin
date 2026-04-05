<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Command;

use Renrhaf\SyliusBrevoPlugin\Service\Contact\ContactSyncServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'renrhaf:brevo:sync-contacts', description: 'Sync all customers to Brevo contacts')]
final class SyncContactsCommand extends Command
{
    public function __construct(
        private readonly ContactSyncServiceInterface $contactSyncService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Syncing contacts to Brevo');

        $result = $this->contactSyncService->syncAll();

        $io->success(sprintf('Done: %d processed, %d failed.', $result['processed'], $result['failed']));

        return Command::SUCCESS;
    }
}
