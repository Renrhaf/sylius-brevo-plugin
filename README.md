# Sylius Brevo Plugin

A comprehensive [Sylius](https://sylius.com/) plugin for [Brevo](https://www.brevo.com/) (formerly Sendinblue) e-commerce integration. Provides full bidirectional sync, real-time event tracking, transactional messaging, and frontend widgets.

## Features

### Core Sync (Batch CLI + Real-time Async)

- **Product Sync** -- Sync your product catalog to Brevo with variant support (parentId linking), configurable brand attribute, and batch processing (up to 100/batch)
- **Order Sync** -- Track order lifecycle in Brevo with variantId on line items, identifiers object, billing address, coupon tracking, and historical order flag
- **Category Sync** -- Sync Sylius taxons to Brevo categories
- **Contact Sync** -- Bidirectional sync between Sylius customers and Brevo contacts with configurable attribute mapping
- **Newsletter** -- Subscribe/unsubscribe with Double Opt-In (DOI) support
- **Cart Tracking** -- Real-time `cart_updated`, `cart_deleted`, and `order_completed` events for Brevo automations

### Real-time Sync via Symfony Messenger

All entities (products, categories, orders, contacts) sync in real-time via Symfony Messenger async queue. Event listeners dispatch messages on Sylius events (`post_create`, `post_update`, `post_complete`), and message handlers fetch entities and call sync services.

### JS Tracker

Auto-injects the Brevo tracking script (`sibautomation.com/sa.js`) with `identify()` calls for logged-in users. Enables browse abandonment flows, page tracking, and Brevo analytics.

- Config: `tracker.enabled` + `api.client_key`
- Template: `@RenrhafSyliusBrevoPlugin/shop/tracker.html.twig`

### Live Chat Widget

Injects the Brevo Conversations widget for real-time customer support.

- Config: `chat.enabled` + `chat.widget_id`
- Template: `@RenrhafSyliusBrevoPlugin/shop/chat_widget.html.twig`

### Transactional Email

Sends Brevo template-based emails via API (`POST /smtp/email`) for order confirmation, order shipped, and customer welcome events.

- Config: `transactional_email.enabled` + template IDs + sender info

### SMS Notifications

Sends transactional SMS via Brevo API on order events. Supports French phone number normalization (0612345678 to +33612345678).

- Config: `sms.enabled` + `sms.sender` + per-event configuration

### Admin Dashboard

Admin page at `/admin/brevo` showing connection status, feature toggles, email stats (30 days), and recent sync logs. Adds a menu item in the admin sidebar.

### Checkout Newsletter Opt-in

Form extension adds a newsletter opt-in checkbox to the checkout address form. Listener subscribes the customer to the configured Brevo list on order completion.

- Config: `newsletter.checkout_subscribe`

### Webhook Handling

- Email events: delivered, opened, clicked, bounced, spam
- Unsubscribe: bidirectional sync with loop prevention
- Token-based authentication

### CLI Commands

| Command | Description |
|---------|-------------|
| `renrhaf:brevo:diagnose` | Test API connection and check configuration |
| `renrhaf:brevo:sync-contacts` | Sync all customers to Brevo contacts |
| `renrhaf:brevo:sync-products` | Sync all enabled products to Brevo |
| `renrhaf:brevo:sync-orders` | Sync orders to Brevo (with optional `--since` filter) |
| `renrhaf:brevo:sync-categories` | Sync all categories (taxons) to Brevo |
| `renrhaf:brevo:purge-logs` | Purge old email and sync logs (configurable retention) |

## Requirements

- PHP 8.2+
- Sylius 2.0+
- Symfony 6.4 / 7.0+
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

### Shop Templates (Tracker + Chat)

To enable the JS tracker and live chat widget, include the plugin templates in your shop layout footer:

```twig
{# templates/bundles/SyliusShopBundle/layout/base.html.twig or your theme layout #}
{% block footer %}
    {{ parent() }}
    {% include '@RenrhafSyliusBrevoPlugin/shop/tracker.html.twig' %}
    {% include '@RenrhafSyliusBrevoPlugin/shop/chat_widget.html.twig' %}
{% endblock %}
```

### Messenger Transport

The plugin dispatches async messages via Symfony Messenger. Configure a transport in your host application:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            'Renrhaf\SyliusBrevoPlugin\Message\*': async
```

## Configuration

Create `config/packages/renrhaf_sylius_brevo.yaml`:

```yaml
renrhaf_sylius_brevo:
    api:
        key: '%env(BREVO_API_KEY)%'
        client_key: '%env(BREVO_CLIENT_KEY)%'        # For JS tracker + identify()

    webhook:
        secret: '%env(BREVO_WEBHOOK_SECRET)%'
        enabled: true
        log_retention_days: 90

    newsletter:
        enabled: true
        list_id: 2                                    # Your Brevo newsletter list ID
        double_opt_in: false
        # doi_template_id: 1                          # Required if double_opt_in is true
        # doi_redirect_url: 'https://example.com/confirmed'
        checkout_subscribe: true                      # Opt-in checkbox at checkout

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
        products:
            sync_enabled: true
            batch_size: 100                           # Max 100 per Brevo API
            brand_attribute: 'brand'                  # Product attribute code for brand
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
            enabled: true                             # For abandoned cart automations

    tracker:
        enabled: true                                 # Brevo JS tracking script

    chat:
        enabled: true
        widget_id: 'your-brevo-conversations-widget-id'

    transactional_email:
        enabled: true
        sender:
            name: 'My Store'
            email: 'noreply@example.com'
        templates:
            order_confirmation: 1                     # Brevo template ID
            order_shipped: 2
            customer_welcome: 3

    sms:
        enabled: true
        sender: 'MyStore'                             # Sender name (max 11 chars)
        events:
            order_confirmation:
                enabled: true
                content: 'Your order #{{ order.number }} has been confirmed.'
            order_shipped:
                enabled: true
                content: 'Your order #{{ order.number }} has been shipped.'
```

Add environment variables to `.env`:

```bash
BREVO_API_KEY=your-api-key
BREVO_CLIENT_KEY=your-client-key
BREVO_WEBHOOK_SECRET=your-secret-token
```

## Usage

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

### Ongoing Sync (Cron)

Real-time sync handles most updates automatically via Messenger. Use cron jobs as a safety net for catching any missed updates:

```bash
# Daily order sync (only recent orders)
0 3 * * * bin/console renrhaf:brevo:sync-orders --since="-2 days"

# Weekly full product sync
0 4 * * 0 bin/console renrhaf:brevo:sync-products

# Monthly log cleanup
0 5 1 * * bin/console renrhaf:brevo:purge-logs
```

### Real-time vs Batch Sync

| Feature | Real-time | Batch (CLI) |
|---------|-----------|-------------|
| Products | Yes (async via Messenger) | `sync-products` |
| Categories | Yes (async via Messenger) | `sync-categories` |
| Orders | Yes (async via Messenger) | `sync-orders` |
| Contacts | Yes (async via Messenger) | `sync-contacts` |
| Cart changes | Yes (sync, immediate) | N/A |

## Webhook Setup

1. Go to your Brevo dashboard > Settings > Webhooks
2. Add a new webhook:
   - **URL**: `https://your-domain.com/webhook/brevo?token=YOUR_SECRET_TOKEN`
   - **Events**: Select all email events (delivered, opened, click, bounce, spam, unsubscribe)
3. Set `BREVO_WEBHOOK_SECRET` in your `.env` to match the token in the URL

The webhook handler will:
- Track email delivery status in the `EmailLog` entity
- Handle unsubscribe events (updates customer newsletter status in Sylius, with loop prevention)
- Log all webhook events for debugging

## E-commerce Integration

### How It Works

1. **Products**: Synced to Brevo's product catalog with variant support (variants linked via `parentId`). Used in email templates and automations.
2. **Orders**: Tracked in Brevo with full detail (variantId on line items, billing address, coupon codes, identifiers). Supports revenue attribution, transactional emails, and segmentation.
3. **Categories**: Synced for product categorization in Brevo.
4. **Cart Events**: Fired in real-time when customers add/remove items, enabling Brevo's abandoned cart automations.

### Abandoned Cart Setup

1. Enable cart tracking in the plugin config (`ecommerce.cart_tracking.enabled: true`)
2. Enable the JS tracker (`tracker.enabled: true` + `api.client_key`)
3. In Brevo, create an automation triggered by `cart_updated` event
4. Add a delay (e.g., 1 hour)
5. Add a condition: "Has NOT triggered `order_completed`"
6. Send your abandoned cart email template

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

### Messenger Transport Routing

The plugin uses Symfony Messenger for async operations. Configure the transport routing in your host application to control how messages are processed (see the [Messenger Transport](#messenger-transport) section above).

## License

MIT -- see [LICENSE](LICENSE).

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Write tests for your changes
4. Ensure code quality: `vendor/bin/ecs check src/` and `vendor/bin/phpstan analyse`
5. Submit a pull request

## Credits

Built by [Renrhaf](https://github.com/Renrhaf) for [Table Indienne](https://www.table-indienne.fr/).
