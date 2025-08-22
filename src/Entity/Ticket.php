<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\TicketAssignment;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DELAYED = 'delayed';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    /**
     * @var Collection|TicketAssignment[]
     */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketAssignment::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $ticketAssignments;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $areaOrigen = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $idSistemaInterno = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->ticketAssignments = new ArrayCollection();
    }

    // Getters and Setters
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [
            self::STATUS_IN_PROGRESS,
            self::STATUS_PENDING,
            self::STATUS_REJECTED,
            self::STATUS_COMPLETED,
            self::STATUS_DELAYED
        ])) {
            throw new \InvalidArgumentException("Invalid status");
        }
        $this->status = $status;
        return $this;
    }


    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getAssignedTo(): Collection
    {
        return $this->ticketAssignments->map(fn(TicketAssignment $assignment) => $assignment->getUser());
    }

    /**
     * @return Collection|TicketAssignment[]
     */
    public function getTicketAssignments(): Collection
    {
        return $this->ticketAssignments;
    }

    public function addAssignedTo(User $user): self
    {
        if (!$this->isAssignedToUser($user)) {
            $assignment = new TicketAssignment();
            $assignment->setUser($user);
            $assignment->setTicket($this);
            $this->ticketAssignments[] = $assignment;
        }
        return $this;
    }

    public function removeAssignedTo(User $user): self
    {
        foreach ($this->ticketAssignments as $assignment) {
            if ($assignment->getUser()->getId() === $user->getId()) {
                $this->ticketAssignments->removeElement($assignment);
                break;
            }
        }
        return $this;
    }

    public function isAssignedToUser(User $user): bool
    {
        foreach ($this->ticketAssignments as $assignment) {
            if ($assignment->getUser()->getId() === $user->getId()) {
                return true;
            }
        }
        return false;
    }

    public function getUserAssignmentTime(User $user): ?\DateTimeInterface
    {
        foreach ($this->ticketAssignments as $assignment) {
            if ($assignment->getUser()->getId() === $user->getId()) {
                return $assignment->getAssignedAt();
            }
        }
        return null;
    }

    /**
     * Get assignments ordered by assignment date (oldest first)
     */
    public function getOrderedAssignments(): Collection
    {
        $assignments = $this->ticketAssignments->toArray();
        usort($assignments, function($a, $b) {
            return $a->getAssignedAt() <=> $b->getAssignedAt();
        });
        return new ArrayCollection($assignments);
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getAreaOrigen(): ?string
    {
        return $this->areaOrigen;
    }

    public function setAreaOrigen(?string $areaOrigen): self
    {
        $this->areaOrigen = $areaOrigen;
        return $this;
    }

    public function getIdSistemaInterno(): ?string
    {
        return $this->idSistemaInterno;
    }

    public function setIdSistemaInterno(?string $idSistemaInterno): self
    {
        $this->idSistemaInterno = $idSistemaInterno;
        return $this;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('Ticket #%s - %s', $this->id, $this->title);
    }
}
