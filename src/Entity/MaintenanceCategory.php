<?php

namespace App\Entity;

use App\Repository\MaintenanceCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaintenanceCategoryRepository::class)]
class MaintenanceCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 100)]
    private $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    #[ORM\Column(type: 'string', length: 20)]
    private $frequency;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $frequencyValue;

    #[ORM\Column(type: 'text', nullable: true)]
    private $instructions;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: MaintenanceTask::class)]
    private $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;
        return $this;
    }

    public function getFrequencyValue(): ?int
    {
        return $this->frequencyValue;
    }

    public function setFrequencyValue(?int $frequencyValue): self
    {
        $this->frequencyValue = $frequencyValue;
        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): self
    {
        $this->instructions = $instructions;
        return $this;
    }

    /**
     * @return Collection|MaintenanceTask[]
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(MaintenanceTask $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setCategory($this);
        }
        return $this;
    }

    public function removeTask(MaintenanceTask $task): self
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getCategory() === $this) {
                $task->setCategory(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
