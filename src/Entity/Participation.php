<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[UniqueEntity(
    fields: ['user', 'event'],
    message: 'You have already registered for this event.',
    errorPath: 'event'
)]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Number of persons is required")]
    #[Assert\Positive(message: "Number of persons must be a positive number")]
    #[Assert\LessThanOrEqual(
        value: 10,
        message: "You cannot register more than {{ limit }} persons at once"
    )]
    private ?int $numberOfPersons = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $registrationDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ticketCode = null;

    #[ORM\Column]
    private ?bool $attended = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $attendanceDate = null;

    public function __construct()
    {
        $this->registrationDate = new \DateTime();
        $this->ticketCode = $this->generateTicketCode();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getNumberOfPersons(): ?int
    {
        return $this->numberOfPersons;
    }

    public function setNumberOfPersons(int $numberOfPersons): static
    {
        $this->numberOfPersons = $numberOfPersons;

        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function setRegistrationDate(\DateTimeInterface $registrationDate): static
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getTicketCode(): ?string
    {
        return $this->ticketCode;
    }

    public function setTicketCode(?string $ticketCode): static
    {
        $this->ticketCode = $ticketCode;

        return $this;
    }

    public function isAttended(): ?bool
    {
        return $this->attended;
    }

    public function setAttended(bool $attended): static
    {
        $this->attended = $attended;

        if ($attended && $this->attendanceDate === null) {
            $this->attendanceDate = new \DateTime();
        }

        return $this;
    }

    public function getAttendanceDate(): ?\DateTimeInterface
    {
        return $this->attendanceDate;
    }

    public function setAttendanceDate(?\DateTimeInterface $attendanceDate): static
    {
        $this->attendanceDate = $attendanceDate;

        return $this;
    }

    private function generateTicketCode(): string
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
    }

    public function getTotalPrice(): float
    {
        if (!$this->event) {
            return 0;
        }
        
        return $this->event->getPrice() * $this->numberOfPersons;
    }

    public function getFormattedTotalPrice(): string
    {
        return number_format($this->getTotalPrice(), 2) . ' TND';
    }
}
