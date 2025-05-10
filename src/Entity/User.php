<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $role = UserRole::USER->value;

    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'participants')]
    private Collection $joinedEvents;

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'creator')]
    private Collection $createdEvents;

    public function __construct()
    {
        $this->joinedEvents = new ArrayCollection();
        $this->createdEvents = new ArrayCollection();
    }

    /* Security Interface Methods */
    
    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        // Convert your enum role to Symfony's expected array format
        return [$this->role];
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary sensitive data, clear it here
        // $this->plainPassword = null;
    }

    // For backwards compatibility (Symfony <5.4)
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /* Original Methods */
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): UserRole
    {
        return UserRole::from($this->role);
    }

    public function setRole(UserRole $role): static
    {
        $this->role = $role->value; 
        return $this;
    }

    public function getJoinedEvents(): Collection
    {
        return $this->joinedEvents;
    }

    public function addJoinedEvent(Event $event): static
    {
        if (!$this->joinedEvents->contains($event)) {
            $this->joinedEvents->add($event);
            $event->addParticipant($this);
        }
        return $this;
    }

    public function removeJoinedEvent(Event $event): static
    {
        if ($this->joinedEvents->removeElement($event)) {
            $event->removeParticipant($this);
        }
        return $this;
    }

    public function getCreatedEvents(): Collection
    {
        return $this->createdEvents;
    }

    public function addCreatedEvent(Event $event): static
    {
        if (!$this->createdEvents->contains($event)) {
            $this->createdEvents->add($event);
            $event->setCreator($this);
        }
        return $this;
    }

    public function removeCreatedEvent(Event $event): static
    {
        if ($this->createdEvents->removeElement($event)) {
            if ($event->getCreator() === $this) {
                $event->setCreator(null);
            }
        }
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->getRole() === UserRole::ADMIN;
    }
    // src/Entity/User.php

    private bool $isActive = true;

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

}