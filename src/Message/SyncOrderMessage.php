<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Message;

final readonly class SyncOrderMessage
{
    public function __construct(
        public int $orderId,
    ) {
    }
}
