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

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 20)]
    private ?string $role = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: Event::class)]
    private Collection $createdEvents;

    #[ORM\ManyToMany(targetEntity: Event::class, inversedBy: 'participants')]
    private Collection $joinedEvents;

    public function __construct()
    {
        $this->createdEvents = new ArrayCollection();
        $this->joinedEvents = new ArrayCollection();
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
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
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
        // If you store any temporary, sensitive data on the user, clear it here
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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        $this->roles = ['ROLE_' . strtoupper($role)];
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

    public function getJoinedEvents(): Collection
    {
        return $this->joinedEvents;
    }

    public function addJoinedEvent(Event $event): static
    {
        if (!$this->joinedEvents->contains($event)) {
            $this->joinedEvents->add($event);
        }
        return $this;
    }

    public function removeJoinedEvent(Event $event): static
    {
        $this->joinedEvents->removeElement($event);
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->getRole() === UserRole::ADMIN->value;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }
}