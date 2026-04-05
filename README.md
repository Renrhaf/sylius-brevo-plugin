# Sylius Brevo Plugin

A comprehensive [Sylius](https://sylius.com/) plugin for [Brevo](https://www.brevo.com/) (formerly Sendinblue) e-commerce integration.

## Features

- **Newsletter Management** — Subscribe/unsubscribe with Double Opt-In support
- **Contact Sync** — Bidirectional sync between Sylius customers and Brevo contacts
- **Product Sync** — Sync your product catalog to Brevo with batch support (up to 100/batch)
- **Order Sync** — Track order lifecycle in Brevo (new, completed, shipped, cancelled, refunded)
- **Cart Tracking** — Abandoned cart events (`cart_updated`, `order_completed`, `cart_deleted`) for Brevo automations
- **Category Sync** — Sync Sylius taxons to Brevo categories
- **Email Logging** — Track all email events (delivered, opened, clicked, bounced)
- **Webhook Handler** — Modular handler for all Brevo webhook events
- **CLI Commands** — Full sync commands for initial import and ongoing maintenance
- **Diagnostics** — Test your API connection and configuration

## Requirements

- PHP 8.2+
- Sylius 2.0+
- Symfony 7.0+
- A [Brevo](https://www.brevo.com/) account with API key

## Installation

```bash
composer require renrhaf/sylius-brevo-plugin
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Renrhaf\SyliusBrevoPlugin\RenrhafSyliusBrevoPlugin::class => ['all' => true],
];
```

Import routes in `config/routes/renrhaf_sylius_brevo.yaml`:

```yaml
renrhaf_sylius_brevo:
    resource: "@RenrhafSyliusBrevoPlugin/config/routes.yaml"
```

Run migrations:

```bash
bin/console doctrine:migrations:migrate
```

## Configuration

Create `config/packages/renrhaf_sylius_brevo.yaml`:

```yaml
renrhaf_sylius_brevo:
    api:
        key: '%env(BREVO_API_KEY)%'
        client_key: '%env(BREVO_CLIENT_KEY)%'    # Optional: for frontend tracking

    webhook:
        secret: '%env(BREVO_WEBHOOK_SECRET)%'
        enabled: true
        log_retention_days: 90

    newsletter:
        enabled: true
        list_id: 2                                # Your Brevo newsletter list ID
        double_opt_in: false
        # doi_template_id: 1                      # Required if double_opt_in is true
        # doi_redirect_url: 'https://example.com/confirmed'

    contacts:
        enabled: true
        sync_on_create: true
        sync_on_update: true
        batch_size: 100
        attribute_mapping:
            firstName: 'PRENOM'
            lastName: 'NOM'
            phoneNumber: 'SMS'

    ecommerce:
        enabled: true
        products:
            sync_enabled: true
            batch_size: 100                       # Max 100 per Brevo API
        orders:
            sync_enabled: true
            sync_statuses:
                - new
                - completed
                - shipped
                - cancelled
                - refunded
        categories:
            sync_enabled: true
        cart_tracking:
            enabled: true                         # For abandoned cart automations

    tracker:
        enabled: true                             # Frontend tracking script
```

Add environment variables to `.env`:

```bash
BREVO_API_KEY=your-api-key
BREVO_CLIENT_KEY=your-client-key        # Optional
BREVO_WEBHOOK_SECRET=your-secret-token
```

## Console Commands

| Command | Description |
|---------|-------------|
| `renrhaf:brevo:diagnose` | Test API connection and check configuration |
| `renrhaf:brevo:sync-contacts` | Sync all customers to Brevo contacts |
| `renrhaf:brevo:sync-products` | Sync all enabled products to Brevo |
| `renrhaf:brevo:sync-orders` | Sync orders to Brevo (with optional `--since` filter) |
| `renrhaf:brevo:sync-categories` | Sync all categories (taxons) to Brevo |
| `renrhaf:brevo:purge-logs` | Purge old email and sync logs (configurable retention) |

### Initial Setup

```bash
# 1. Test your connection
bin/console renrhaf:brevo:diagnose

# 2. Sync everything
bin/console renrhaf:brevo:sync-categories
bin/console renrhaf:brevo:sync-products
bin/console renrhaf:brevo:sync-contacts
bin/console renrhaf:brevo:sync-orders
```

### Ongoing Sync (cron)

```bash
# Daily order sync (only recent orders)
0 3 * * * bin/console renrhaf:brevo:sync-orders --since="-2 days"

# Weekly full product sync
0 4 * * 0 bin/console renrhaf:brevo:sync-products

# Monthly log cleanup
0 5 1 * * bin/console renrhaf:brevo:purge-logs
```

## Webhook Setup

1. Go to your Brevo dashboard > Settings > Webhooks
2. Add a new webhook:
   - **URL**: `https://your-domain.com/webhook/brevo?token=YOUR_SECRET_TOKEN`
   - **Events**: Select all email events (delivered, opened, click, bounce, spam, unsubscribe)
3. Set `BREVO_WEBHOOK_SECRET` in your `.env` to match the token in the URL

The webhook handler will:
- Track email delivery status in the `EmailLog` entity
- Handle unsubscribe events (updates customer newsletter status in Sylius)
- Log all webhook events for debugging

## E-commerce Integration

### How It Works

1. **Products**: Synced to Brevo's product catalog for use in email templates and automations
2. **Orders**: Tracked in Brevo for revenue attribution, order confirmation emails, and segmentation
3. **Categories**: Synced for product categorization in Brevo
4. **Cart Events**: Fired in real-time when customers add/remove items, enabling Brevo's abandoned cart automations

### Abandoned Cart Setup

1. Enable cart tracking in the plugin config (`ecommerce.cart_tracking.enabled: true`)
2. In Brevo, create an automation triggered by `cart_updated` event
3. Add a delay (e.g., 1 hour)
4. Add a condition: "Has NOT triggered `order_completed`"
5. Send your abandoned cart email template

### Real-time vs Batch Sync

| Feature | Real-time | Batch (CLI) |
|---------|-----------|-------------|
| Customer create/update | Yes (via events) | `sync-contacts` |
| Order completion | Yes (via events) | `sync-orders` |
| Cart changes | Yes (via events) | N/A |
| Products | No | `sync-products` |
| Categories | No | `sync-categories` |

## Extending

All services are defined with interfaces. You can override any service by decorating it:

```yaml
# config/services.yaml
services:
    App\Mapper\CustomProductMapper:
        decorates: 'Renrhaf\SyliusBrevoPlugin\Mapper\ProductMapperInterface'
```

### Custom Webhook Handlers

Create a service that implements `WebhookHandlerInterface` and tag it:

```yaml
services:
    App\Webhook\MyCustomHandler:
        tags: ['renrhaf_sylius_brevo.webhook_handler']
```

## License

MIT — see [LICENSE](LICENSE).

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Write tests for your changes
4. Ensure code quality: `vendor/bin/ecs check src/` and `vendor/bin/phpstan analyse`
5. Submit a pull request

## Credits

Built by [Renrhaf](https://github.com/Renrhaf) for [Table Indienne](https://www.table-indienne.fr/).
