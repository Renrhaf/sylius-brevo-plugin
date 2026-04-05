<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Mapper;

use Sylius\Component\Core\Model\CustomerInterface;

final class ContactMapper implements ContactMapperInterface
{
    /** @param array<string, string> $attributeMapping */
    public function __construct(
        private readonly array $attributeMapping,
    ) {
    }

    public function map(CustomerInterface $customer): array
    {
        $sourceValues = [
            'firstName' => $customer->getFirstName() ?? '',
            'lastName' => $customer->getLastName() ?? '',
            'phoneNumber' => $customer->getPhoneNumber() ?? '',
            'gender' => $customer->getGender(),
            'birthday' => $customer->getBirthday()?->format('Y-m-d') ?? '',
        ];

        $attributes = [];

        foreach ($this->attributeMapping as $syliusField => $brevoAttribute) {
            if (isset($sourceValues[$syliusField]) && '' !== $sourceValues[$syliusField]) {
                $attributes[$brevoAttribute] = $sourceValues[$syliusField];
            }
        }

        return $attributes;
    }
}
