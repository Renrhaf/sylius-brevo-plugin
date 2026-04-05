<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Contact;

use Sylius\Component\Core\Model\CustomerInterface;

interface ContactSyncServiceInterface
{
    public function syncCustomer(CustomerInterface $customer): void;

    /** @return array{processed: int, failed: int} */
    public function syncAll(): array;
}
