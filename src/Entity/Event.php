<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[Vich\Uploadable]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Event name is required")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Event name must be at least {{ limit }} characters long",
        maxMessage: "Event name cannot be longer than {{ limit }} characters"
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Description is required")]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Location is required")]
    private ?string $location = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "Start date is required")]
    #[Assert\GreaterThanOrEqual(
        value: "today",
        message: "Start date must be in the future"
    )]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "End date is required")]
    #[Assert\GreaterThan(
        propertyPath: "startDate",
        message: "End date must be after start date"
    )]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Capacity is required")]
    #[Assert\Positive(message: "Capacity must be a positive number")]
    private ?int $capacity = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Price is required")]
    #[Assert\GreaterThanOrEqual(
        value: 0,
        message: "Price must be a positive number or zero"
    )]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Category is required")]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFilename = null;

    #[Vich\UploadableField(mapping: 'event_images', fileNameProperty: 'imageFilename')]
    private ?File $imageFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organizer $organizer = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Participation::class, orphanRemoval: true)]
    private Collection $participations;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?int $availableSpots = null;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
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
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;
        $this->availableSpots = $capacity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            $this->updatedAt = new \DateTime();
        }
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getOrganizer(): ?Organizer
    {
        return $this->organizer;
    }

    public function setOrganizer(?Organizer $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setEvent($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getEvent() === $this) {
                $participation->setEvent(null);
            }
        }

        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getAvailableSpots(): ?int
    {
        return $this->availableSpots;
    }

    public function setAvailableSpots(int $availableSpots): static
    {
        $this->availableSpots = $availableSpots;

        return $this;
    }

    public function updateAvailableSpots(): void
    {
        $totalParticipants = 0;
        foreach ($this->participations as $participation) {
            $totalParticipants += $participation->getNumberOfPersons();
        }
        $this->availableSpots = $this->capacity - $totalParticipants;
    }

    public function isFull(): bool
    {
        return $this->availableSpots <= 0;
    }

    public function getFormattedPrice(): string
    {
        if ($this->price == 0) {
            return 'Free';
        }
        return number_format($this->price, 2) . ' TND';
    }

    public function getFormattedDate(): string
    {
        $startDate = $this->startDate->format('M d, Y');
        $endDate = $this->endDate->format('M d, Y');
        
        if ($startDate === $endDate) {
            return $startDate . ' (' . $this->startDate->format('H:i') . ' - ' . $this->endDate->format('H:i') . ')';
        }
        
        return $startDate . ' - ' . $endDate;
    }

    public function getDuration(): string
    {
        $diff = $this->startDate->diff($this->endDate);
        
        if ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '');
        }
        
        $hours = $diff->h;
        $minutes = $diff->i;
        
        if ($hours > 0) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . 
                   ($minutes > 0 ? ' ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '') : '');
        }
        
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }
}
