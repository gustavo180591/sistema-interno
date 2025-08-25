<?php

namespace App\Entity;

use App\Repository\MaintenanceLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaintenanceLogRepository::class)]
class MaintenanceLog
{
    const TYPE_STATUS_CHANGE = 'status_change';
    const TYPE_COMMENT = 'comment';
    const TYPE_ASSIGNMENT = 'assignment';
    const TYPE_SCHEDULE_UPDATE = 'schedule_update';
    const TYPE_COMPLETION = 'completion';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: MaintenanceTask::class, inversedBy: 'logs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private $task;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\Column(type: 'string', length: 50)]
    private $type;

    #[ORM\Column(type: 'text')]
    private $message;

    #[ORM\Column(type: 'json', nullable: true)]
    private $details = [];

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTask(): ?MaintenanceTask
    {
        return $this->task;
    }

    public function setTask(?MaintenanceTask $task): self
    {
        $this->task = $task;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getFormattedMessage(): string
    {
        $message = $this->message;
        
        if (!empty($this->details)) {
            foreach ($this->details as $key => $value) {
                $message = str_replace("{" . $key . "}", $value, $message);
            }
        }
        
        return $message;
    }

    public function getIcon(): string
    {
        switch ($this->type) {
            case self::TYPE_STATUS_CHANGE:
                return 'sync-alt';
            case self::TYPE_COMMENT:
                return 'comment';
            case self::TYPE_ASSIGNMENT:
                return 'user-tag';
            case self::TYPE_SCHEDULE_UPDATE:
                return 'calendar-alt';
            case self::TYPE_COMPLETION:
                return 'check-circle';
            default:
                return 'info-circle';
        }
    }

    public function getBadgeClass(): string
    {
        switch ($this->type) {
            case self::TYPE_STATUS_CHANGE:
                return 'bg-info';
            case self::TYPE_COMMENT:
                return 'bg-secondary';
            case self::TYPE_ASSIGNMENT:
                return 'bg-primary';
            case self::TYPE_SCHEDULE_UPDATE:
                return 'bg-warning text-dark';
            case self::TYPE_COMPLETION:
                return 'bg-success';
            default:
                return 'bg-secondary';
        }
    }
}
