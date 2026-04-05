<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Api;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Brevo API v3 client using Symfony HttpClient.
 * Handles authentication, error handling, and rate limiting.
 */
final class BrevoClient implements BrevoClientInterface
{
    private const BASE_URL = 'https://api.brevo.com/v3';
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 500;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
    ) {
    }

    // --- Account ---

    public function getAccount(): array
    {
        return $this->request('GET', '/account');
    }

    // --- Contacts ---

    public function createContact(string $email, array $attributes = [], array $listIds = [], bool $updateEnabled = true): void
    {
        $body = [
            'email' => $email,
            'updateEnabled' => $updateEnabled,
        ];

        if ([] !== $attributes) {
            $body['attributes'] = $attributes;
        }

        if ([] !== $listIds) {
            $body['listIds'] = $listIds;
        }

        $this->request('POST', '/contacts', $body);
    }

    public function deleteContact(string $email): void
    {
        $this->request('DELETE', '/contacts/' . urlencode($email));
    }

    public function getContact(string $email): ?array
    {
        try {
            return $this->request('GET', '/contacts/' . urlencode($email));
        } catch (BrevoApiException $e) {
            if (404 === $e->getStatusCode()) {
                return null;
            }

            throw $e;
        }
    }

    public function addContactToList(int $listId, string $email): void
    {
        $this->request('POST', '/contacts/lists/' . $listId . '/contacts/add', [
            'emails' => [$email],
        ]);
    }

    public function removeContactFromList(int $listId, string $email): void
    {
        $this->request('POST', '/contacts/lists/' . $listId . '/contacts/remove', [
            'emails' => [$email],
        ]);
    }

    // --- DOI ---

    public function createDoiContact(string $email, array $attributes, int $listId, int $templateId, string $redirectUrl): void
    {
        $this->request('POST', '/contacts/doubleOptinConfirmation', [
            'email' => $email,
            'attributes' => $attributes,
            'includeListIds' => [$listId],
            'templateId' => $templateId,
            'redirectionUrl' => $redirectUrl,
        ]);
    }

    // --- Ecommerce: Products ---

    public function createOrUpdateProduct(array $product): void
    {
        $product['updateEnabled'] = true;
        $this->request('POST', '/products', $product);
    }

    public function batchCreateOrUpdateProducts(array $products): void
    {
        $this->request('POST', '/products/batch', [
            'products' => $products,
            'updateEnabled' => true,
        ]);
    }

    // --- Ecommerce: Orders ---

    public function createOrUpdateOrder(array $order): void
    {
        $this->request('POST', '/orders/status', $order);
    }

    public function batchCreateOrUpdateOrders(array $orders, bool $historical = true): void
    {
        $this->request('POST', '/orders/status/batch', [
            'orders' => $orders,
            'historical' => $historical,
        ]);
    }

    // --- Ecommerce: Categories ---

    public function createOrUpdateCategory(array $category): void
    {
        $category['updateEnabled'] = true;
        $this->request('POST', '/categories', $category);
    }

    public function batchCreateOrUpdateCategories(array $categories): void
    {
        $this->request('POST', '/categories/batch', [
            'categories' => $categories,
            'updateEnabled' => true,
        ]);
    }

    // --- Ecommerce: Activation ---

    public function activateEcommerce(): void
    {
        $this->request('POST', '/ecommerce/activate');
    }

    // --- Events ---

    public function trackEvent(string $eventName, string $email, array $eventData = []): void
    {
        $body = [
            'event_name' => $eventName,
            'identifiers' => ['email_id' => $email],
        ];

        if ([] !== $eventData) {
            $body['event_properties'] = $eventData;
        }

        $this->request('POST', '/events', $body);
    }

    // --- Transactional Email ---

    public function sendTransactionalEmail(int $templateId, array $to, array $params = [], ?array $sender = null): void
    {
        $body = [
            'templateId' => $templateId,
            'to' => $to,
        ];

        if ([] !== $params) {
            $body['params'] = $params;
        }

        if (null !== $sender) {
            $body['sender'] = $sender;
        }

        $this->request('POST', '/smtp/email', $body);
    }

    // --- SMS ---

    public function sendTransactionalSms(string $sender, string $recipient, string $content): void
    {
        $this->request('POST', '/transactionalSMS/sms', [
            'sender' => $sender,
            'recipient' => $recipient,
            'content' => $content,
            'type' => 'transactional',
        ]);
    }

    // --- Internal ---

    /**
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $endpoint, ?array $body = null): array
    {
        $options = [
            'headers' => [
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        if (null !== $body) {
            $options['json'] = $body;
        }

        $url = self::BASE_URL . $endpoint;
        $attempt = 0;

        while (true) {
            ++$attempt;

            try {
                $response = $this->httpClient->request($method, $url, $options);
                $statusCode = $response->getStatusCode();

                // 204 No Content — success with no body
                if (204 === $statusCode) {
                    return [];
                }

                // 2xx — parse JSON response
                if ($statusCode >= 200 && $statusCode < 300) {
                    $content = $response->getContent(false);

                    return '' !== $content ? json_decode($content, true, 512, \JSON_THROW_ON_ERROR) : [];
                }

                // 429 Rate limited — retry with backoff
                if (429 === $statusCode && $attempt < self::MAX_RETRIES) {
                    $this->logger->warning('Brevo API rate limited, retrying', [
                        'attempt' => $attempt,
                        'endpoint' => $endpoint,
                    ]);
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt);

                    continue;
                }

                // Error
                $errorBody = $response->getContent(false);

                throw new BrevoApiException(
                    sprintf('Brevo API error %d on %s %s: %s', $statusCode, $method, $endpoint, $errorBody),
                    $statusCode,
                );
            } catch (BrevoApiException $e) {
                throw $e;
            } catch (\Throwable $e) {
                if ($attempt < self::MAX_RETRIES) {
                    $this->logger->warning('Brevo API request failed, retrying', [
                        'attempt' => $attempt,
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt);

                    continue;
                }

                throw new BrevoApiException(
                    sprintf('Brevo API request failed after %d attempts: %s', self::MAX_RETRIES, $e->getMessage()),
                    0,
                    $e,
                );
            }
        }
    }
}
