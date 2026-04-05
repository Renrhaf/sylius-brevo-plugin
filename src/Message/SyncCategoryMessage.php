<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Message;

final readonly class SyncCategoryMessage
{
    public function __construct(
        public int $taxonId,
    ) {
    }
}
