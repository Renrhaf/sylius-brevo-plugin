<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class RenrhafSyliusBrevoExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // API
        $container->setParameter('renrhaf_sylius_brevo.api.key', $config['api']['key']);
        $container->setParameter('renrhaf_sylius_brevo.api.client_key', $config['api']['client_key']);

        // Webhook
        $container->setParameter('renrhaf_sylius_brevo.webhook.secret', $config['webhook']['secret']);
        $container->setParameter('renrhaf_sylius_brevo.webhook.enabled', $config['webhook']['enabled']);
        $container->setParameter('renrhaf_sylius_brevo.webhook.log_retention_days', $config['webhook']['log_retention_days']);

        // Newsletter
        $container->setParameter('renrhaf_sylius_brevo.newsletter.enabled', $config['newsletter']['enabled']);
        $container->setParameter('renrhaf_sylius_brevo.newsletter.list_id', $config['newsletter']['list_id']);
        $container->setParameter('renrhaf_sylius_brevo.newsletter.double_opt_in', $config['newsletter']['double_opt_in']);
        $container->setParameter('renrhaf_sylius_brevo.newsletter.doi_template_id', $config['newsletter']['doi_template_id']);
        $container->setParameter('renrhaf_sylius_brevo.newsletter.doi_redirect_url', $config['newsletter']['doi_redirect_url']);
        $container->setParameter('renrhaf_sylius_brevo.newsletter.checkout_subscribe', $config['newsletter']['checkout_subscribe']);

        // Contacts
        $container->setParameter('renrhaf_sylius_brevo.contacts.enabled', $config['contacts']['enabled']);
        $container->setParameter('renrhaf_sylius_brevo.contacts.sync_on_create', $config['contacts']['sync_on_create']);
        $container->setParameter('renrhaf_sylius_brevo.contacts.sync_on_update', $config['contacts']['sync_on_update']);
        $container->setParameter('renrhaf_sylius_brevo.contacts.batch_size', $config['contacts']['batch_size']);
        $container->setParameter('renrhaf_sylius_brevo.contacts.attribute_mapping', $config['contacts']['attribute_mapping']);

        // Ecommerce
        $container->setParameter('renrhaf_sylius_brevo.ecommerce.enabled', $config['ecommerce']['enabled']);
        $container->setParameter('renrhaf_sylius_brevo.ecommerce.products.sync_enabled', $config['ecommerce']['products']['sync_enabled']);
        $container->setParameter('renrhaf_sylius_brevo.ecommerce.products.batch_size', $config['ecommerce']['products']['batch_size']);
        $container->setParameter('renrhaf_sylius_brevo.ecommerce.products.brand_attribute', $config['ecommerce']['products']['brand_attribute']);
        $container->setParameter('renrhaf_sylius_brevo.ecommerce.orders.sync_enabled', $config['ecommerce']['orders']['sync_enabled']);
        $container->setParameter('renrhaf_sylius_brevo.ecommerce.orders.sync_statuses', $config['ecommerce']['orders']['sync_statuses']);
        $container->setParameter('renrhaf_sylius_brevo.ecommerce.categories.sync_enabled', $config['ecommerce']['categories']['sync_enabled']);
        $container->setParameter('renrhaf_sylius_brevo.ecommerce.cart_tracking.enabled', $config['ecommerce']['cart_tracking']['enabled']);

        // Tracker
        $container->setParameter('renrhaf_sylius_brevo.tracker.enabled', $config['tracker']['enabled']);

        // Chat
        $container->setParameter('renrhaf_sylius_brevo.chat.enabled', $config['chat']['enabled']);
        $container->setParameter('renrhaf_sylius_brevo.chat.widget_id', $config['chat']['widget_id']);

        // Transactional Email
        $container->setParameter('renrhaf_sylius_brevo.transactional_email.enabled', $config['transactional_email']['enabled']);
        $container->setParameter('renrhaf_sylius_brevo.transactional_email.sender.name', $config['transactional_email']['sender']['name']);
        $container->setParameter('renrhaf_sylius_brevo.transactional_email.sender.email', $config['transactional_email']['sender']['email']);
        $container->setParameter('renrhaf_sylius_brevo.transactional_email.templates', $config['transactional_email']['templates']);

        // SMS
        $container->setParameter('renrhaf_sylius_brevo.sms.enabled', $config['sms']['enabled']);
        $container->setParameter('renrhaf_sylius_brevo.sms.sender', $config['sms']['sender']);
        $container->setParameter('renrhaf_sylius_brevo.sms.events', $config['sms']['events']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Register Twig paths for plugin templates
        $container->prependExtensionConfig('twig', [
            'paths' => [
                \dirname(__DIR__, 2) . '/templates' => 'RenrhafSyliusBrevoPlugin',
            ],
        ]);
    }
}
