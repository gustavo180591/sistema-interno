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

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

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

    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function setCompletedAt(?\DateTimeImmutable $completedAt): static { $this->completedAt = $completedAt; return $this; }

    public function getDepartamentoNombre(): string
    {
        $departamentos = [
            // Presidencia y Secretarías
            1 => 'Presidencia',
            2 => 'Secretaría',
            3 => 'Prosecretaria Legislativa',
            4 => 'Prosecretaria Administrativa',
            
            // Direcciones Generales
            5 => 'Dirección General de Gestión Financiera y Administrativa',
            6 => 'Dirección General de Administración y Contabilidad',
            7 => 'Dirección General de Asuntos Legislativos y Comisiones',
            
            // Direcciones Principales
            8 => 'Dirección de Gestión y TIC',
            9 => 'Dirección de Desarrollo Humano',
            10 => 'Dirección de Personal',
            11 => 'Dirección de RR.HH',
            12 => 'Dirección de Asuntos Jurídicos',
            13 => 'Dirección de Contabilidad y Presupuesto',
            14 => 'Dirección de Liquidación de Sueldos',
            15 => 'Dirección de Abastecimiento',
            16 => 'Dirección de Salud Mental',
            17 => 'Dirección de Obras e Infraestructura',
            18 => 'Dirección de RR.PP y Ceremonial',
            19 => 'Dirección de Digesto Jurídico',
            20 => 'Dirección de Prensa',
            
            // Departamentos
            21 => 'Departamento de Archivos',
            22 => 'Departamento de Compras y Licitaciones',
            23 => 'Departamento de Bienes Patrimoniales',
            24 => 'Departamento de Cómputos',
            25 => 'Departamento de Reconocimiento Médico',
            26 => 'Departamento de Asuntos Legislativos',
            27 => 'Departamento de Comisiones',
            28 => 'Departamento de Mesa de Entradas y Salidas',
            29 => 'Departamento de Sumario',
            
            // Divisiones
            30 => 'División Presupuesto y Rendición de Cuentas',
            31 => 'División Cuota Alimentaria y EMB. JUD.',
            
            // Secciones
            32 => 'Sección Computos',
            33 => 'Sección Previsional',
            34 => 'Sección Sumario',
            35 => 'Sección Liquidación de Sueldos y Jornales',
            36 => 'Sección Suministro',
            37 => 'Sección Servicios Generales',
            38 => 'Sección Legajo y Archivo',
            39 => 'Sección Seguridad',
            40 => 'Sección Mantenimiento',
            41 => 'Sección Cuerpo Taquígrafos',
            42 => 'Sección Biblioteca',
            
            // Áreas Especiales
            43 => 'Coordinación de Jurídico y Administración',
            44 => 'Agenda HCD',
            45 => 'Municipalidad de Posadas',
            46 => 'Defensora del Pueblo',
            
            // Concejalías
            47 => 'Concejal Dib Jair',
            48 => 'Concejal Velazquez Pablo',
            49 => 'Concejal Traid Laura',
            50 => 'Concejal Scromeda Luciana',
            51 => 'Concejal Salom Judith',
            52 => 'Concejal Mazal Malena',
            53 => 'Concejal Martinez Horacio',
            54 => 'Concejal Koch Santiago',
            55 => 'Concejal Jimenez Eva',
            56 => 'Concejal Gomez Valeria',
            57 => 'Concejal Cardozo Hector',
            58 => 'Concejal Argañaraz Pablo',
            59 => 'Concejal Almiron Samira',
            60 => 'Concejal Dardo Romero',
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
