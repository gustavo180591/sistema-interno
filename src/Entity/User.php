<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Este email ya está registrado.')]
#[UniqueEntity(fields: ['username'], message: 'Este nombre de usuario ya está en uso.')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Nombre(s) y apellido(s) – opcional pero útil para tu UI
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    public ?string $nombre = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    public ?string $apellido = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'El email es obligatorio.')]
    #[Assert\Email(message: 'Email inválido.')]
    private ?string $email = null;

    #[ORM\Column(length: 60, unique: true)]
    #[Assert\NotBlank(message: 'El nombre de usuario es obligatorio.')]
    #[Assert\Length(min: 3, max: 60)]
    private ?string $username = null;

    /**
     * Roles en JSON. Ej: ["ROLE_ADMIN"], ["ROLE_AUDITOR"], ["ROLE_USER"]
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * Hash de contraseña (no guardes plano).
     */
    #[ORM\Column]
    #[Assert\NotBlank(message: 'La contraseña es obligatoria.', groups: ['registration'])]
    #[Assert\Length(min: 6, minMessage: 'La contraseña debe tener al menos {{ limit }} caracteres.', groups: ['registration'])]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $lastLoginAt = null;

    /**
     * @var Collection|TicketAssignment[]
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: TicketAssignment::class)]
    private Collection $ticketAssignments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now');
        $this->isActive = true;
        $this->ticketAssignments = new ArrayCollection();
    }

    // ====== Getters/Setters ======

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(?string $apellido): self
    {
        $this->apellido = $apellido;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower($email);
        return $this;
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', 
            $this->nombre ?? '', 
            $this->apellido ?? ''
        )) ?: $this->getUsername();
    }

    public function setUsername(string $username): self
    {
        $this->username = strtolower($username);
        return $this;
    }

    /**
     * Identificador visual para Security (puede ser email).
     */
    public function getUserIdentifier(): string
    {
        return (string) ($this->email ?? $this->username ?? '');
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // Garantizamos al menos ROLE_USER
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_values(array_unique($roles));
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): self
    {
        // normalizamos (mayúsculas, sin espacios)
        $roles = array_map(fn($r) => strtoupper(trim($r)), $roles);
        $this->roles = $roles;
        return $this;
    }

    /**
     * Helper para renderizar bonito en Twig (Administrador, Auditor, Usuario)
     */
    public function getRoleDisplayNamesForTwig(): array
    {
        $map = [
            'ROLE_ADMIN'   => 'Administrador',
            'ROLE_AUDITOR' => 'Auditor',
            'ROLE_USER'    => 'Usuario',
        ];

        $out = [];
        foreach ($this->getRoles() as $code) {
            $out[] = $map[$code] ?? $code;
        }
        // orden opcional: Admin → Auditor → Usuario
        $order = ['Administrador', 'Auditor', 'Usuario'];
        usort($out, fn($a, $b) => array_search($a, $order) <=> array_search($b, $order));

        return $out;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Sets the user's password (hashed)
     */
    public function setPassword(string $hashed): self
    {
        $this->password = $hashed;
        return $this;
    }
    
    /**
     * Sets a plain password (to be hashed by the UserPasswordHasher)
     */
    public function setPlainPassword(string $plainPassword): self
    {
        // This is just a setter, the actual hashing will be done by the UserPasswordHasher
        $this->password = $plainPassword;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si guardás un plainPassword temporal, limpiarlo acá.
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getLastLoginAt(): ?\DateTime
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTime $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    // ===== Conveniencias para la UI =====

    public function getNombreCompleto(): string
    {
        return trim(($this->nombre ?? '') . ' ' . ($this->apellido ?? '')) ?: ($this->username ?? '');
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    /**
     * @return Collection<int, TicketAssignment>
     */
    public function getTicketAssignments(): Collection
    {
        return $this->ticketAssignments;
    }

    public function addTicketAssignment(TicketAssignment $ticketAssignment): static
    {
        if (!$this->ticketAssignments->contains($ticketAssignment)) {
            $this->ticketAssignments->add($ticketAssignment);
            $ticketAssignment->setUser($this);
        }

        return $this;
    }

    public function removeTicketAssignment(TicketAssignment $ticketAssignment): static
    {
        if ($this->ticketAssignments->removeElement($ticketAssignment)) {
            // set the owning side to null (unless already changed)
            if ($ticketAssignment->getUser() === $this) {
                $ticketAssignment->setUser(null);
            }
        }

        return $this;
    }
}
