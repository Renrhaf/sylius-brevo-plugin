<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'renrhaf_brevo_email_log')]
#[ORM\Index(columns: ['email'], name: 'idx_email')]
#[ORM\Index(columns: ['status'], name: 'idx_status')]
#[ORM\Index(columns: ['created_at'], name: 'idx_created_at')]
class EmailLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'message_id', type: Types::STRING, length: 255, nullable: true)]
    private ?string $messageId = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255)]
    private string $email;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 500, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(name: 'email_type', type: Types::STRING, length: 50, nullable: true)]
    private ?string $emailType = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 30)]
    private string $status = 'sent';

    #[ORM\Column(name: 'clicked_url', type: Types::STRING, length: 2000, nullable: true)]
    private ?string $clickedUrl = null;

    #[ORM\Column(name: 'opened_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $openedAt = null;

    #[ORM\Column(name: 'clicked_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $clickedAt = null;

    #[ORM\Column(name: 'bounced_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $bouncedAt = null;

    #[ORM\Column(name: 'error_reason', type: Types::TEXT, nullable: true)]
    private ?string $errorReason = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function setMessageId(?string $messageId): void
    {
        $this->messageId = $messageId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = $subject;
    }

    public function getEmailType(): ?string
    {
        return $this->emailType;
    }

    public function setEmailType(?string $emailType): void
    {
        $this->emailType = $emailType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getClickedUrl(): ?string
    {
        return $this->clickedUrl;
    }

    public function setClickedUrl(?string $clickedUrl): void
    {
        $this->clickedUrl = $clickedUrl;
    }

    public function getOpenedAt(): ?\DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function markAsOpened(): void
    {
        if (null === $this->openedAt) {
            $this->openedAt = new \DateTimeImmutable();
            $this->status = 'opened';
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function markAsClicked(?string $url = null): void
    {
        $this->markAsOpened();
        if (null === $this->clickedAt) {
            $this->clickedAt = new \DateTimeImmutable();
            $this->status = 'clicked';
        }
        if (null !== $url) {
            $this->clickedUrl = $url;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsBounced(?string $reason = null): void
    {
        $this->bouncedAt = new \DateTimeImmutable();
        $this->status = 'bounced';
        $this->errorReason = $reason;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
