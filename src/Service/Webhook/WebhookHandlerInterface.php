<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Webhook;

interface WebhookHandlerInterface
{
    /** @param array<string, mixed> $payload */
    public function handle(array $payload): void;

    public function supports(string $eventType): bool;
}
