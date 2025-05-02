<?php

namespace App\Entity;

use App\Repository\ReactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReactionRepository::class)]
class Reaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $emoji = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'reactions')]
    private ?Post $post = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;
    #[ORM\Column(type: 'smallint')]
    private ?int $typeIndex = null;

    public function getTypeIndex(): ?int
    {
        return $this->typeIndex;
    }

    public function setTypeIndex(int $typeIndex): self
    {
        $this->typeIndex = $typeIndex;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmoji(): ?string
    {
        return $this->emoji;
    }

    public function setEmoji(string $emoji): self
    {
        $this->emoji = $emoji;
        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): self
    {
        $this->post = $post;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
}
