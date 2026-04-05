<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Controller\Webhook;

use Psr\Log\LoggerInterface;
use Renrhaf\SyliusBrevoPlugin\Service\Webhook\WebhookHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles incoming Brevo webhook events.
 * Authenticates via a secret token query parameter.
 */
final class BrevoWebhookController extends AbstractController
{
    /** @param iterable<WebhookHandlerInterface> $handlers */
    public function __construct(
        private readonly iterable $handlers,
        private readonly LoggerInterface $logger,
        private readonly ?string $webhookSecret,
        private readonly bool $webhookEnabled,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->webhookEnabled) {
            return new JsonResponse(['error' => 'Webhooks disabled'], Response::HTTP_NOT_FOUND);
        }

        // Verify token
        if (null !== $this->webhookSecret && '' !== $this->webhookSecret) {
            $token = $request->query->get('token');

            if ($token !== $this->webhookSecret) {
                $this->logger->error('Brevo webhook: Invalid token', ['ip' => $request->getClientIp()]);

                return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
        }

        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $event = $payload['event'] ?? null;

        if (!\is_string($event)) {
            return new JsonResponse(['error' => 'Missing event type'], Response::HTTP_BAD_REQUEST);
        }

        $this->logger->info('Brevo webhook received', [
            'event' => $event,
            'email' => $payload['email'] ?? 'unknown',
        ]);

        foreach ($this->handlers as $handler) {
            if ($handler->supports($event)) {
                try {
                    $handler->handle($payload);
                } catch (\Throwable $e) {
                    $this->logger->error('Brevo webhook handler error', [
                        'handler' => $handler::class,
                        'event' => $event,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return new JsonResponse(['status' => 'ok']);
    }
}
