<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Exposes Brevo configuration as Twig globals for use in shop templates.
 */
final class BrevoExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly bool $trackerEnabled,
        private readonly ?string $clientKey,
        private readonly bool $chatEnabled,
        private readonly ?string $chatWidgetId,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'brevo_tracker_enabled' => $this->trackerEnabled && null !== $this->clientKey,
            'brevo_client_key' => $this->clientKey,
            'brevo_chat_enabled' => $this->chatEnabled && null !== $this->chatWidgetId,
            'brevo_chat_widget_id' => $this->chatWidgetId,
        ];
    }
}
