<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Newsletter;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoApiException;
use Renrhaf\SyliusBrevoPlugin\Api\BrevoClientInterface;

final class NewsletterService implements NewsletterServiceInterface
{
    public function __construct(
        private readonly BrevoClientInterface $brevoClient,
        private readonly LoggerInterface $logger,
        private readonly int $listId,
        private readonly bool $doubleOptIn,
        private readonly ?int $doiTemplateId,
        private readonly ?string $doiRedirectUrl,
    ) {
    }

    public function subscribe(string $email, string $firstName = '', string $lastName = ''): void
    {
        $attributes = array_filter([
            'PRENOM' => $firstName,
            'NOM' => $lastName,
        ]);

        try {
            if ($this->doubleOptIn && null !== $this->doiTemplateId && null !== $this->doiRedirectUrl) {
                $this->brevoClient->createDoiContact(
                    $email,
                    $attributes,
                    $this->listId,
                    $this->doiTemplateId,
                    $this->doiRedirectUrl,
                );
                $this->logger->info('DOI contact created in Brevo', ['email' => $email]);

                return;
            }

            $this->brevoClient->createContact($email, $attributes, [$this->listId]);
            $this->logger->info('Contact subscribed to newsletter in Brevo', ['email' => $email]);
        } catch (BrevoApiException $e) {
            $this->logger->error('Failed to subscribe contact in Brevo', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function unsubscribe(string $email): void
    {
        try {
            $this->brevoClient->removeContactFromList($this->listId, $email);
            $this->logger->info('Contact unsubscribed from newsletter in Brevo', ['email' => $email]);
        } catch (BrevoApiException $e) {
            $this->logger->error('Failed to unsubscribe contact in Brevo', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function isSubscribed(string $email): bool
    {
        try {
            $contact = $this->brevoClient->getContact($email);

            if (null === $contact) {
                return false;
            }

            $listIds = $contact['listIds'] ?? [];

            return \in_array($this->listId, $listIds, true);
        } catch (BrevoApiException) {
            return false;
        }
    }
}
