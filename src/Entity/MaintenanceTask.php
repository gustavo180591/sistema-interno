<?php

namespace App\Entity;

use App\Repository\MaintenanceTaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaintenanceTaskRepository::class)]
class MaintenanceTask
{
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_SKIPPED = 'skipped';


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    #[ORM\ManyToOne(targetEntity: MaintenanceCategory::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private $category;

    #[ORM\Column(type: 'string', length: 20)]
    private $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'datetime')]
    private $scheduledDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $completedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $assignedTo;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $completedBy;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'datetime')]
    private $updatedAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private $notes;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $actualDuration; // in minutes

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: MaintenanceLog::class, orphanRemoval: true)]
    private $logs;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->logs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): ?MaintenanceCategory
    {
        return $this->category;
    }

    public function setCategory(?MaintenanceCategory $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getScheduledDate(): ?\DateTimeInterface
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(\DateTimeInterface $scheduledDate): self
    {
        $this->scheduledDate = $scheduledDate;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getCompletedBy(): ?User
    {
        return $this->completedBy;
    }

    public function setCompletedBy(?User $completedBy): self
    {
        $this->completedBy = $completedBy;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getActualDuration(): ?int
    {
        return $this->actualDuration;
    }

    public function setActualDuration(?int $actualDuration): self
    {
        $this->actualDuration = $actualDuration;
        return $this;
    }

    /**
     * @return Collection|MaintenanceLog[]
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(MaintenanceLog $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs[] = $log;
            $log->setTask($this);
        }
        return $this;
    }

    public function removeLog(MaintenanceLog $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getTask() === $this) {
                $log->setTask(null);
            }
        }
        return $this;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE || 
               ($this->status === self::STATUS_PENDING && $this->scheduledDate < new \DateTime());
    }

    public function getStatusClass(): string
    {
        switch ($this->status) {
            case self::STATUS_COMPLETED:
                return 'success';
            case self::STATUS_IN_PROGRESS:
                return 'info';
            case self::STATUS_OVERDUE:
                return 'danger';
            case self::STATUS_PENDING:
                return $this->isOverdue() ? 'warning' : 'secondary';
            default:
                return 'secondary';
        }
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_PROGRESS => 'En Progreso',
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_OVERDUE => 'Atrasada',
            self::STATUS_SKIPPED => 'Omitida'
        ];
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
