<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Mapper;

use Sylius\Component\Core\Model\TaxonInterface;

interface CategoryMapperInterface
{
    /** @return array<string, mixed> Brevo category payload */
    public function map(TaxonInterface $taxon, string $locale): array;
}
