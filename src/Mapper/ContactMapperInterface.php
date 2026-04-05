<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Mapper;

use Sylius\Component\Core\Model\CustomerInterface;

interface ContactMapperInterface
{
    /** @return array<string, string> Brevo contact attributes */
    public function map(CustomerInterface $customer): array;
}
