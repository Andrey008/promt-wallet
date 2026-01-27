<?php

namespace App\Entity;

use App\Repository\PromptTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PromptTemplateRepository::class)]
#[ORM\Table(name: 'prompt_templates')]
#[ORM\HasLifecycleCallbacks]
class PromptTemplate
{
    public const MODEL_UNIVERSAL = 'universal';
    public const MODEL_CLAUDE = 'claude';
    public const MODEL_CURSOR = 'cursor';
    public const MODEL_COPILOT = 'copilot';

    public const MODELS = [
        self::MODEL_UNIVERSAL => 'Universal',
        self::MODEL_CLAUDE => 'Claude',
        self::MODEL_CURSOR => 'Cursor',
        self::MODEL_COPILOT => 'Copilot',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 200)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $body = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [self::MODEL_UNIVERSAL, self::MODEL_CLAUDE, self::MODEL_CURSOR, self::MODEL_COPILOT])]
    private ?string $targetModel = self::MODEL_UNIVERSAL;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'promptTemplates')]
    #[ORM\JoinTable(name: 'prompt_template_tags')]
    private Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function getTargetModel(): ?string
    {
        return $this->targetModel;
    }

    public function setTargetModel(string $targetModel): static
    {
        $this->targetModel = $targetModel;
        return $this;
    }

    public function getTargetModelLabel(): string
    {
        return self::MODELS[$this->targetModel] ?? $this->targetModel;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public function clearTags(): static
    {
        $this->tags->clear();
        return $this;
    }

    /**
     * Extract placeholders from body
     * @return array<string>
     */
    public function getPlaceholders(): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $this->body ?? '', $matches);
        return array_unique($matches[1] ?? []);
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}
