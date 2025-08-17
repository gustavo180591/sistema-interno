<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $ticketId = null;

    #[ORM\Column(type: 'text')]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'smallint')]
    private ?int $departamento = null;

    #[ORM\Column(length: 20)]
    private ?string $estado = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketCollaborator::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $collaborators;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->estado = 'pendiente'; // default value
        $this->collaborators = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getTicketId(): ?string { return $this->ticketId; }
    public function setTicketId(string $ticketId): static { $this->ticketId = $ticketId; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(string $descripcion): static { $this->descripcion = $descripcion; return $this; }

    public function getDepartamento(): ?int { return $this->departamento; }
    public function setDepartamento(int $departamento): static { $this->departamento = $departamento; return $this; }

    public function getEstado(): ?string { return $this->estado; }
    public function setEstado(string $estado): static { $this->estado = $estado; return $this; }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Collection<int, TicketCollaborator>
     */
    public function getCollaborators(): Collection
    {
        return $this->collaborators;
    }

    public function addCollaborator(TicketCollaborator $collaborator): static
    {
        if (!$this->collaborators->contains($collaborator)) {
            $this->collaborators->add($collaborator);
            $collaborator->setTicket($this);
        }

        return $this;
    }

    public function removeCollaborator(TicketCollaborator $collaborator): static
    {
        if ($this->collaborators->removeElement($collaborator)) {
            // set the owning side to null (unless already changed)
            if ($collaborator->getTicket() === $this) {
                $collaborator->setTicket(null);
            }
        }

        return $this;
    }

    public function isCollaborator(User $user): bool
    {
        foreach ($this->collaborators as $collaborator) {
            if ($collaborator->getUser() === $user) {
                return true;
            }
        }
        return false;
    }
}
