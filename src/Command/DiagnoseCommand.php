<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Command;

use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'renrhaf:brevo:diagnose', description: 'Test Brevo API connection and configuration')]
final class DiagnoseCommand extends Command
{
    public function __construct(
        private readonly BrevoClientInterface $brevoClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Brevo API Diagnostic');

        try {
            $account = $this->brevoClient->getAccount();
            $io->success('API connection successful');

            $io->table(['Field', 'Value'], [
                ['Company', $account['companyName'] ?? 'N/A'],
                ['Email', $account['email'] ?? 'N/A'],
                ['Plan', $account['plan'][0]['type'] ?? 'N/A'],
                ['Credits', $account['plan'][0]['credits'] ?? 'N/A'],
            ]);
        } catch (\Throwable $e) {
            $io->error('API connection failed: ' . $e->getMessage());

            return Command::FAILURE;
        }

        // Test ecommerce activation
        try {
            $this->brevoClient->activateEcommerce();
            $io->success('E-commerce is activated');
        } catch (\Throwable $e) {
            $io->warning('E-commerce activation check failed: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
