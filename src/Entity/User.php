<?php 
namespace App\Entity;

use App\Repository\UserRepository;
use App\Doctrine\DBAL\Types\EnumType; // Подключаем кастомный тип
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: "Username is required.")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Username must be at least 3 characters long.", maxMessage: "Username cannot be longer than 255 characters.")]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Password is required.")]
    #[Assert\Length(min: 6, minMessage: "Password must be at least 6 characters long.")]
    private ?string $password = null;


    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $loginTime = null;

    #[ORM\Column(type: 'currency_enum')]
    private $currency;

    #[ORM\Column(type: 'role_enum')]
    private $role;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private ?string $totalPnl = null;
    
    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private ?string $equity = null;

    #[ORM\OneToOne(targetEntity: self::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $agent = null;


    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateCreated;

    // Symfony Security Methods
    public function getRoles(): array
    {
        return [$this->role ?? 'USER'];
    }

    public function eraseCredentials(): void
    {
        // clean plainPassword if needed
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    // Add this method to fix the issue
    public function getSalt(): ?string
    {
        return null;  // This can be null for modern password hashing
    }

    // Getters & Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getLoginTime(): ?\DateTimeInterface
    {
        return $this->loginTime;
    }

    public function setLoginTime(\DateTimeInterface $loginTime): static
    {
        $this->loginTime = $loginTime;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getRole(): ?string
    {
        return 'ROLE_' . $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getTotalPnl(): ?float
    {
        return $this->totalPnl;
    }

    public function setTotalPnl(float $totalPnl): static
    {
        $this->totalPnl = $totalPnl;
        return $this;
    }

    public function getEquity(): ?float
    {
        return $this->equity;
    }

    public function setEquity(float $equity): static
    {
        $this->equity = $equity;
        return $this;
    }

    public function getAgent(): ?self
    {
        return $this->agent;
    }

    public function setAgent(self $agent): static
    {
        $this->agent = $agent;
        return $this;
    }

    public function getDateCreated(): \DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }
}
