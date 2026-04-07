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
            'phoneNumber' => $this->sanitizePhone($customer->getPhoneNumber() ?? ''),
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

    /**
     * Sanitizes a phone number for Brevo API (requires E.164 format: +XXXXXXXXXXX).
     * Returns empty string if the number cannot be normalized.
     */
    private function sanitizePhone(string $phone): string
    {
        if ('' === $phone) {
            return '';
        }

        // Strip spaces, dashes, dots, parentheses
        $cleaned = preg_replace('/[\s\-.\(\)]+/', '', $phone);

        if (null === $cleaned || '' === $cleaned) {
            return '';
        }

        // Must start with + and contain only digits after that (E.164)
        if (1 === preg_match('/^\+[1-9]\d{6,14}$/', $cleaned)) {
            return $cleaned;
        }

        // If starts with 00, convert to +
        if (str_starts_with($cleaned, '00')) {
            $converted = '+' . substr($cleaned, 2);
            if (1 === preg_match('/^\+[1-9]\d{6,14}$/', $converted)) {
                return $converted;
            }
        }

        // Cannot normalize — skip to avoid Brevo API error
        return '';
    }
}
