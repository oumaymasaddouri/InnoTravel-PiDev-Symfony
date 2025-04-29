<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "feedback", uniqueConstraints: [
    new ORM\UniqueConstraint(name: "unique_user_feedback", columns: ["userId"])
])]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "feedbacks")]
    #[ORM\JoinColumn(name: 'userId', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    private ?User $userId;

    #[Assert\Range(
        notInRangeMessage: 'Rating must be between {{ min }} and {{ max }}.',
        min: 1,
        max: 5
    )]
    #[ORM\Column(type: "integer")]
    private int $rating;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($value): void
    {
        $this->id = $value;
    }

    public function getUserId(): ?User
    {
        return $this->userId;
    }

    public function setUserId(?User $user): void
    {
        $this->userId = $user;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $value): void
    {
        $this->rating = $value;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $value): void
    {
        $this->content = $value;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $value): void
    {
        $this->date = $value;
    }
}


