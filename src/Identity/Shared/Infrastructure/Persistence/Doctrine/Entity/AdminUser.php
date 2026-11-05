<?php

declare(strict_types=1);

namespace App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\SoftDeletableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\SoftDeletableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\ToggleableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\ToggleableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Entity\BaseEntity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'app_admin_user')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[UniqueEntity(fields: ['email'])]
#[UniqueEntity(fields: ['username'])]
class AdminUser extends BaseEntity implements UserInterface, PasswordAuthenticatedUserInterface, ToggleableInterface, TimestampableInterface, SoftDeletableInterface
{
    use ToggleableTrait;
    use TimestampableTrait;
    use SoftDeletableTrait;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(min: 5, max: 180)]
    private string $email;

    #[ORM\Column(name: 'username', type: Types::STRING, length: 60, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 60)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9._-]+$/', message: 'admin_user.username.invalid_chars')]
    private string $username;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(name: 'roles', type: Types::JSON)]
    #[Assert\NotNull]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, minMessage: 'admin_user.roles.min_count')]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\Choice(choices: [
            'ROLE_USER',
            'ROLE_ADMIN',
            'ROLE_SUPER_ADMIN',
        ]),
    ])]
    private array $roles = ['ROLE_USER'];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(name: 'password', type: Types::STRING, length: 255)]
    #[Assert\Length(max: 255)]
    private string $password = '';

    #[Assert\NotBlank(groups: ['admin:create'])]
    #[Assert\Length(min: 8, max: 255, groups: ['admin:create', 'admin:update_password'])]
    #[Assert\Regex(
        pattern: '/(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+/',
        message: 'admin_user.plain_password.weak',
        groups: ['admin:create', 'admin:update_password']
    )]
    private ?string $plainPassword = null;

    public function __construct()
    {
        parent::__construct();

        $this->initializeCreatedAt();
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        if (empty($this->username)) {
            return $this->id->toString();
        }

        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function softDelete(): void
    {
        $token = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 8);
        $suffix = sprintf('__deleted_%s', $token);

        $this->setEmail($this->getEmail().$suffix);
        $this->setUsername($this->getUsername().$suffix);
        $this->setEnabled(false);
        $this->delete();
    }
}
