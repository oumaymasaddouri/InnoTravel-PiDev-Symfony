<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Event;

#[ORM\Entity]
class Organizer
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    private string $contact_info;

    #[ORM\Column(type: "string", length: 255)]
    private string $website_url;

    #[ORM\Column(type: "boolean")]
    private bool $verified;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getContact_info()
    {
        return $this->contact_info;
    }

    public function setContact_info($value)
    {
        $this->contact_info = $value;
    }

    public function getWebsite_url()
    {
        return $this->website_url;
    }

    public function setWebsite_url($value)
    {
        $this->website_url = $value;
    }

    public function getVerified()
    {
        return $this->verified;
    }

    public function setVerified($value)
    {
        $this->verified = $value;
    }

    #[ORM\OneToMany(mappedBy: "idOrganizer", targetEntity: Event::class)]
    private Collection $events;

        public function getEvents(): Collection
        {
            return $this->events;
        }
    
        public function addEvent(Event $event): self
        {
            if (!$this->events->contains($event)) {
                $this->events[] = $event;
                $event->setIdOrganizer($this);
            }
    
            return $this;
        }
    
        public function removeEvent(Event $event): self
        {
            if ($this->events->removeElement($event)) {
                // set the owning side to null (unless already changed)
                if ($event->getIdOrganizer() === $this) {
                    $event->setIdOrganizer(null);
                }
            }
    
            return $this;
        }
}
