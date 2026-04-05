<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Command;

use Renrhaf\SyliusBrevoPlugin\Service\Ecommerce\OrderSyncServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'renrhaf:brevo:sync-orders', description: 'Sync orders to Brevo')]
final class SyncOrdersCommand extends Command
{
    public function __construct(
        private readonly OrderSyncServiceInterface $orderSyncService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('since', null, InputOption::VALUE_REQUIRED, 'Sync orders updated since this date (Y-m-d)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Syncing orders to Brevo');

        $since = null;
        $sinceStr = $input->getOption('since');

        if (\is_string($sinceStr) && '' !== $sinceStr) {
            $since = new \DateTimeImmutable($sinceStr);
            $io->note('Syncing orders since ' . $since->format('Y-m-d'));
        }

        $result = $this->orderSyncService->syncAll($since);

        $io->success(sprintf('Done: %d processed, %d failed.', $result['processed'], $result['failed']));

        return Command::SUCCESS;
    }
}
