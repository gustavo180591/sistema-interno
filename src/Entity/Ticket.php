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
    const STATUS_DELAYED = 'delayed';
    const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observation = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'completed_by_id', referencedColumnName: 'id', nullable: true)]
    private ?User $completedBy = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 20)]
    private string $priority = 'medium';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    /**
     * @var Collection|TicketAssignment[]
     */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketAssignment::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $ticketAssignments;

    /**
     * @var Collection<int, TicketUpdate>
     */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketUpdate::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $updates;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: Note::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $notes;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $takenBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $takenAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $areaOrigen = null;

    #[ORM\Column(length: 50, unique: true, nullable: true)]
    #[Assert\Unique(message: 'Este ID Externo ya está en uso. Por favor, ingrese un ID único.')]
    private ?string $idSistemaInterno = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $proposedStatus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $proposalNote = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $proposedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $assignedTo = null;

    /**
     * @var Collection|User[]
     */
    private Collection $formAssignedUsers;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->ticketAssignments = new ArrayCollection();
        $this->updates = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->formAssignedUsers = new ArrayCollection();
    }

    /**
     * @return Collection|User[]
     */
    public function getFormAssignedUsers(): Collection
    {
        if ($this->formAssignedUsers === null) {
            $this->formAssignedUsers = new ArrayCollection();
        }
        return $this->formAssignedUsers;
    }

    /**
     * @param User[]|Collection|User|null $assignedUsers
     */
    public function setFormAssignedUsers($assignedUsers): self
    {
        if ($assignedUsers === null) {
            $assignedUsers = [];
        } elseif ($assignedUsers instanceof Collection) {
            $assignedUsers = $assignedUsers->toArray();
        } elseif (!is_array($assignedUsers)) {
            $assignedUsers = [$assignedUsers];
        }
        
        // Ensure we only have User objects
        $assignedUsers = array_filter($assignedUsers, function($user) {
            return $user instanceof User;
        });
        
        $this->formAssignedUsers = new ArrayCollection($assignedUsers);
        return $this;
    }

    public function addFormAssignedUser(User $user): self
    {
        if (!$this->getFormAssignedUsers()->contains($user)) {
            $this->getFormAssignedUsers()->add($user);
        }
        return $this;
    }

    public function removeFormAssignedUser(User $user): self
    {
        $this->getFormAssignedUsers()->removeElement($user);
        return $this;
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

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): self
    {
        $this->observation = $observation;
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

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
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
    public function getAssignedUsers(): Collection
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

    /**
     * @return Collection|TicketUpdate[]
     */
    public function getUpdates(): Collection
    {
        return $this->updates;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes[] = $note;
            $note->setTicket($this);
        }

        return $this;
    }

    public function removeNote(Note $note): self
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getTicket() === $this) {
                $note->setTicket(null);
            }
        }

        return $this;
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

    public function addTicketAssignment(TicketAssignment $assignment): self
    {
        if (!$this->ticketAssignments->contains($assignment)) {
            $this->ticketAssignments[] = $assignment;
            $assignment->setTicket($this);
        }
        return $this;
    }

    public function removeTicketAssignment(TicketAssignment $assignment): self
    {
        if ($this->ticketAssignments->removeElement($assignment)) {
            // set the owning side to null (unless already changed)
            if ($assignment->getTicket() === $this) {
                $assignment->setTicket(null);
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

    public function getProposedStatus(): ?string
    {
        return $this->proposedStatus;
    }

    public function setProposedStatus(?string $proposedStatus): self
    {
        $this->proposedStatus = $proposedStatus;
        return $this;
    }

    public function getProposalNote(): ?string
    {
        return $this->proposalNote;
    }

    public function setProposalNote(?string $proposalNote): self
    {
        $this->proposalNote = $proposalNote;
        return $this;
    }

    public function getProposedBy(): ?User
    {
        return $this->proposedBy;
    }

    public function setProposedBy(?User $proposedBy): self
    {
        $this->proposedBy = $proposedBy;
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

    public function setDueDate(?\DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getTakenBy(): ?User
    {
        return $this->takenBy;
    }

    public function setTakenBy(?User $takenBy): self
    {
        $this->takenBy = $takenBy;
        $this->takenAt = $takenBy ? new \DateTimeImmutable() : null;
        
        return $this;
    }

    public function getTakenAt(): ?\DateTimeInterface
    {
        return $this->takenAt;
    }

    public function setTakenAt(\DateTimeInterface $takenAt): self
    {
        $this->takenAt = $takenAt;
        
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('Ticket #%s - %s', $this->id, $this->title);
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function addUpdate(TicketUpdate $update): static
    {
        if (!$this->updates->contains($update)) {
            $this->updates->add($update);
            $update->setTicket($this);
        }

        return $this;
    }

    public function removeUpdate(TicketUpdate $update): static
    {
        if ($this->updates->removeElement($update)) {
            // set the owning side to null (unless already changed)
            if ($update->getTicket() === $this) {
                $update->setTicket(null);
            }
        }

        return $this;
    }
}
