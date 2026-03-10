<?php

namespace App\Entity;

use App\Repository\PromptCompositionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PromptCompositionRepository::class)]
#[ORM\Table(name: 'prompt_compositions')]
#[ORM\Index(columns: ['owner_id', 'created_at'], name: 'idx_composition_owner_date')]
class PromptComposition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $composedText = null;

    #[ORM\Column(length: 200)]
    private ?string $templateTitle = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $projectName = null;

    #[ORM\Column(type: Types::JSON)]
    private array $contextTitles = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComposedText(): ?string
    {
        return $this->composedText;
    }

    public function setComposedText(string $composedText): static
    {
        $this->composedText = $composedText;
        return $this;
    }

    public function getTemplateTitle(): ?string
    {
        return $this->templateTitle;
    }

    public function setTemplateTitle(string $templateTitle): static
    {
        $this->templateTitle = $templateTitle;
        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(?string $projectName): static
    {
        $this->projectName = $projectName;
        return $this;
    }

    public function getContextTitles(): array
    {
        return $this->contextTitles;
    }

    public function setContextTitles(array $contextTitles): static
    {
        $this->contextTitles = $contextTitles;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }
}
