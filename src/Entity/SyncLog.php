<?php

declare(strict_types=1);

namespace Renrhaf\SyliusBrevoPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'renrhaf_brevo_sync_log')]
#[ORM\Index(columns: ['type'], name: 'idx_sync_type')]
class SyncLog
{
    public const TYPE_CONTACTS = 'contacts';
    public const TYPE_PRODUCTS = 'products';
    public const TYPE_ORDERS = 'orders';
    public const TYPE_CATEGORIES = 'categories';

    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 30)]
    private string $type;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 20)]
    private string $status = self::STATUS_RUNNING;

    #[ORM\Column(name: 'items_processed', type: Types::INTEGER)]
    private int $itemsProcessed = 0;

    #[ORM\Column(name: 'items_failed', type: Types::INTEGER)]
    private int $itemsFailed = 0;

    #[ORM\Column(name: 'error_message', type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'started_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'completed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getItemsProcessed(): int
    {
        return $this->itemsProcessed;
    }

    public function incrementProcessed(int $count = 1): void
    {
        $this->itemsProcessed += $count;
    }

    public function incrementFailed(int $count = 1): void
    {
        $this->itemsFailed += $count;
    }

    public function markCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markFailed(string $message): void
    {
        $this->status = self::STATUS_FAILED;
        $this->errorMessage = $message;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }
}
