<?php

namespace App\Entity;

use App\Repository\MachineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MachineRepository::class)]
class Machine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'machines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Office $office = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $inventoryNumber = null;

    #[ORM\Column(type: 'integer')]
    private int $ramGb = 8;

    #[ORM\Column(type: 'boolean')]
    private bool $institutional = true;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $cpu = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $os = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $disk = null;

    public function getId(): ?int { return $this->id; }

    public function getOffice(): ?Office { return $this->office; }
    public function setOffice(?Office $office): static { $this->office = $office; return $this; }

    public function getInventoryNumber(): ?string { return $this->inventoryNumber; }
    public function setInventoryNumber(string $num): static { $this->inventoryNumber = $num; return $this; }

    public function getRamGb(): int { return $this->ramGb; }
    public function setRamGb(int $ramGb): static { $this->ramGb = $ramGb; return $this; }

    public function isInstitutional(): bool { return $this->institutional; }
    public function setInstitutional(bool $institutional): static { $this->institutional = $institutional; return $this; }

    public function getCpu(): ?string { return $this->cpu; }
    public function setCpu(?string $cpu): static { $this->cpu = $cpu; return $this; }

    public function getOs(): ?string { return $this->os; }
    public function setOs(?string $os): static { $this->os = $os; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getDisk(): ?string { return $this->disk; }
    public function setDisk(?string $disk): static { $this->disk = $disk; return $this; }
}
