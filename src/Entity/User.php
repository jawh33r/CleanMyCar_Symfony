<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
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

    // Not stored in database, used only for form
    private ?string $plainPassword = null;

    #[ORM\OneToOne(targetEntity: Client::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Client $client = null;

    #[ORM\OneToOne(targetEntity: Admin::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Admin $admin = null;

    #[ORM\OneToOne(targetEntity: Ouvrier::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Ouvrier $ouvrier = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Always add ROLE_USER if no roles are set
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        if ($client === null && $this->client !== null) {
            $this->client->setUser(null);
        }

        if ($client !== null && $client->getUser() !== $this) {
            $client->setUser($this);
        }

        $this->client = $client;

        return $this;
    }

    public function getAdmin(): ?Admin
    {
        return $this->admin;
    }

    public function setAdmin(?Admin $admin): static
    {
        if ($admin === null && $this->admin !== null) {
            $this->admin->setUser(null);
        }

        if ($admin !== null && $admin->getUser() !== $this) {
            $admin->setUser($this);
        }

        $this->admin = $admin;

        return $this;
    }

    public function getOuvrier(): ?Ouvrier
    {
        return $this->ouvrier;
    }

    public function setOuvrier(?Ouvrier $ouvrier): static
    {
        if ($ouvrier === null && $this->ouvrier !== null) {
            $this->ouvrier->setUser(null);
        }

        if ($ouvrier !== null && $ouvrier->getUser() !== $this) {
            $ouvrier->setUser($this);
        }

        $this->ouvrier = $ouvrier;

        return $this;
    }

    public function __toString(): string
    {
        $client = $this->getClient();
        $name = $client ? $client->getNom() : 'N/A';
        return sprintf('%s (ID: %d) - %s', $name, $this->getId(), $this->getEmail());
    }
}

