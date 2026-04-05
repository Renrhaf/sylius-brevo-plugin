<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Service\Webhook\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Renrhaf\SyliusBrevoPlugin\Entity\EmailLog;
use Renrhaf\SyliusBrevoPlugin\Service\Webhook\WebhookHandlerInterface;

/**
 * Handles email delivery events: delivered, opened, click, bounce, error, blocked, spam.
 */
final class EmailEventHandler implements WebhookHandlerInterface
{
    private const SUPPORTED_EVENTS = [
        'delivered', 'opened', 'open', 'click', 'clicked',
        'hard_bounce', 'soft_bounce', 'blocked', 'error', 'spam',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(string $eventType): bool
    {
        return \in_array($eventType, self::SUPPORTED_EVENTS, true);
    }

    public function handle(array $payload): void
    {
        $messageId = $payload['message-id'] ?? null;
        $event = $payload['event'] ?? '';
        $email = $payload['email'] ?? '';

        if (null === $messageId || '' === $email) {
            return;
        }

        $repository = $this->entityManager->getRepository(EmailLog::class);
        $emailLog = $repository->findOneBy(['messageId' => $messageId]);

        if (null === $emailLog) {
            // Create a new log entry for this message
            $emailLog = new EmailLog($email);
            $emailLog->setMessageId($messageId);
            $this->entityManager->persist($emailLog);
        }

        match ($event) {
            'delivered' => $emailLog->setStatus('delivered'),
            'opened', 'open' => $emailLog->markAsOpened(),
            'click', 'clicked' => $emailLog->markAsClicked($payload['link'] ?? null),
            'hard_bounce', 'soft_bounce' => $emailLog->markAsBounced($event),
            'blocked' => $emailLog->markAsBounced('blocked'),
            'error' => $emailLog->markAsBounced($payload['reason'] ?? 'error'),
            'spam' => $emailLog->markAsBounced('spam complaint'),
            default => null,
        };

        $this->entityManager->flush();
    }
}
