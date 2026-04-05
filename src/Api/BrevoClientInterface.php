<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Api;

/**
 * Abstraction over the Brevo HTTP API v3.
 * All methods return decoded JSON responses or throw BrevoApiException.
 */
interface BrevoClientInterface
{
    // --- Account ---
    /** @return array<string, mixed> */
    public function getAccount(): array;

    // --- Contacts ---
    /** @param array<string, mixed> $attributes */
    public function createContact(string $email, array $attributes = [], array $listIds = [], bool $updateEnabled = true): void;

    public function deleteContact(string $email): void;

    /** @return array<string, mixed>|null */
    public function getContact(string $email): ?array;

    public function addContactToList(int $listId, string $email): void;

    public function removeContactFromList(int $listId, string $email): void;

    // --- DOI ---
    /** @param array<string, mixed> $attributes */
    public function createDoiContact(string $email, array $attributes, int $listId, int $templateId, string $redirectUrl): void;

    // --- Ecommerce: Products ---
    /** @param array<string, mixed> $product */
    public function createOrUpdateProduct(array $product): void;

    /** @param array<int, array<string, mixed>> $products */
    public function batchCreateOrUpdateProducts(array $products): void;

    // --- Ecommerce: Orders ---
    /** @param array<string, mixed> $order */
    public function createOrUpdateOrder(array $order): void;

    /** @param array<int, array<string, mixed>> $orders */
    public function batchCreateOrUpdateOrders(array $orders): void;

    // --- Ecommerce: Categories ---
    /** @param array<string, mixed> $category */
    public function createOrUpdateCategory(array $category): void;

    /** @param array<int, array<string, mixed>> $categories */
    public function batchCreateOrUpdateCategories(array $categories): void;

    // --- Ecommerce: Activation ---
    public function activateEcommerce(): void;

    // --- Events ---
    /** @param array<string, mixed> $eventData */
    public function trackEvent(string $eventName, string $email, array $eventData = []): void;
}
