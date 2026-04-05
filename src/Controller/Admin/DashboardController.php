<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Controller\Admin;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly BrevoClientInterface $brevoClient,
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(): Response
    {
        // Test API connection
        $account = null;
        $connectionError = null;

        try {
            $account = $this->brevoClient->getAccount();
        } catch (\Throwable $e) {
            $connectionError = $e->getMessage();
            $this->logger->error('Brevo connection test failed', ['error' => $connectionError]);
        }

        // Get recent sync logs
        $syncLogs = [];

        try {
            $syncLogs = $this->connection->fetchAllAssociative(
                'SELECT * FROM renrhaf_brevo_sync_log ORDER BY started_at DESC LIMIT 10',
            );
        } catch (\Throwable) {
            // Table may not exist yet
        }

        // Get email log stats
        $emailStats = [];

        try {
            $emailStats = $this->connection->fetchAssociative(
                'SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = \'delivered\' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                    SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked,
                    SUM(CASE WHEN bounced_at IS NOT NULL THEN 1 ELSE 0 END) as bounced
                FROM renrhaf_brevo_email_log
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
            ) ?: [];
        } catch (\Throwable) {
            // Table may not exist yet
        }

        // Gather feature status from container parameters
        $features = [
            'contacts' => $this->getParameter('renrhaf_sylius_brevo.contacts.enabled'),
            'products' => $this->getParameter('renrhaf_sylius_brevo.ecommerce.products.sync_enabled'),
            'orders' => $this->getParameter('renrhaf_sylius_brevo.ecommerce.orders.sync_enabled'),
            'categories' => $this->getParameter('renrhaf_sylius_brevo.ecommerce.categories.sync_enabled'),
            'cart_tracking' => $this->getParameter('renrhaf_sylius_brevo.ecommerce.cart_tracking.enabled'),
            'newsletter' => $this->getParameter('renrhaf_sylius_brevo.newsletter.enabled'),
            'tracker' => $this->getParameter('renrhaf_sylius_brevo.tracker.enabled'),
            'chat' => $this->getParameter('renrhaf_sylius_brevo.chat.enabled'),
            'transactional_email' => $this->getParameter('renrhaf_sylius_brevo.transactional_email.enabled'),
            'sms' => $this->getParameter('renrhaf_sylius_brevo.sms.enabled'),
            'webhook' => $this->getParameter('renrhaf_sylius_brevo.webhook.enabled'),
        ];

        return $this->render('@RenrhafSyliusBrevoPlugin/admin/dashboard/index.html.twig', [
            'account' => $account,
            'connectionError' => $connectionError,
            'syncLogs' => $syncLogs,
            'emailStats' => $emailStats,
            'features' => $features,
        ]);
    }
}
