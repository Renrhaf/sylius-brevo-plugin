<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'renrhaf:brevo:purge-logs', description: 'Purge old email and sync logs')]
final class PurgeLogsCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly int $retentionDays,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Retention period in days', (string) $this->retentionDays);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $cutoff = (new \DateTimeImmutable())->modify("-{$days} days")->format('Y-m-d H:i:s');

        $emailDeleted = $this->connection->executeStatement(
            'DELETE FROM renrhaf_brevo_email_log WHERE created_at < :cutoff',
            ['cutoff' => $cutoff],
        );

        $syncDeleted = $this->connection->executeStatement(
            'DELETE FROM renrhaf_brevo_sync_log WHERE started_at < :cutoff',
            ['cutoff' => $cutoff],
        );

        $io->success(sprintf('Purged %d email logs and %d sync logs older than %d days.', $emailDeleted, $syncDeleted, $days));

        return Command::SUCCESS;
    }
}
