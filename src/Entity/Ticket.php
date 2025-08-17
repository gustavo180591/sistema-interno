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

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $pedido = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: TicketCollaborator::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $collaborators;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: Task::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['completed' => 'ASC', 'createdAt' => 'DESC'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->estado = 'pendiente'; // default value
        $this->collaborators = new ArrayCollection();
        $this->tasks = new ArrayCollection();
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

    public function getPedido(): ?string { return $this->pedido; }
    public function setPedido(?string $pedido): static { $this->pedido = $pedido; return $this; }

    public function getDepartamentoNombre(): string
    {
        $departamentos = [
            1 => 'Sistemas',
            2 => 'Administración',
            3 => 'Recursos Humanos',
            4 => 'Contabilidad',
            5 => 'Ventas',
            6 => 'Atención al Cliente',
            7 => 'Logística',
            8 => 'Almacén',
            9 => 'Compras',
            10 => 'Dirección',
        ];

        return $departamentos[$this->departamento] ?? 'Desconocido';
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setTicket($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getTicket() === $this) {
                $task->setTicket(null);
            }
        }

        return $this;
    }

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
