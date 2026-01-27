<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tags')]
#[ORM\Index(columns: ['name'], name: 'idx_tag_name')]
#[UniqueEntity(fields: ['name'], message: 'A tag with this name already exists.')]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Color must be a valid HEX color (e.g., #FF5733)')]
    private ?string $color = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Context>
     */
    #[ORM\ManyToMany(targetEntity: Context::class, mappedBy: 'tags')]
    private Collection $contexts;

    /**
     * @var Collection<int, PromptTemplate>
     */
    #[ORM\ManyToMany(targetEntity: PromptTemplate::class, mappedBy: 'tags')]
    private Collection $promptTemplates;

    public function __construct()
    {
        $this->contexts = new ArrayCollection();
        $this->promptTemplates = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = strtolower(trim($name));
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
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

    /**
     * @return Collection<int, Context>
     */
    public function getContexts(): Collection
    {
        return $this->contexts;
    }

    public function addContext(Context $context): static
    {
        if (!$this->contexts->contains($context)) {
            $this->contexts->add($context);
            $context->addTag($this);
        }
        return $this;
    }

    public function removeContext(Context $context): static
    {
        if ($this->contexts->removeElement($context)) {
            $context->removeTag($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, PromptTemplate>
     */
    public function getPromptTemplates(): Collection
    {
        return $this->promptTemplates;
    }

    public function addPromptTemplate(PromptTemplate $promptTemplate): static
    {
        if (!$this->promptTemplates->contains($promptTemplate)) {
            $this->promptTemplates->add($promptTemplate);
            $promptTemplate->addTag($this);
        }
        return $this;
    }

    public function removePromptTemplate(PromptTemplate $promptTemplate): static
    {
        if ($this->promptTemplates->removeElement($promptTemplate)) {
            $promptTemplate->removeTag($this);
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
