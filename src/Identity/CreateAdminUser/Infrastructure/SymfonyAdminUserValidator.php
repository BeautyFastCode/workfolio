<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Infrastructure;

use App\Identity\CreateAdminUser\Application\AdminUserValidator;
use App\Identity\CreateAdminUser\Application\CreateAdminUserCommand;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class SymfonyAdminUserValidator implements AdminUserValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {}

    public function validate(CreateAdminUserCommand $command): array
    {
        $violations = [];
        $emailViolations = $this->messages($this->validator->validate($command->email, [
            new Assert\NotBlank(),
            new Assert\Email(),
            new Assert\Length(min: 5, max: 180),
        ]));

        if ($emailViolations !== []) {
            $violations['email'] = $emailViolations;
        }

        $usernameViolations = $this->messages($this->validator->validate($command->username, [
            new Assert\NotBlank(),
            new Assert\Length(min: 3, max: 60),
            new Assert\Regex(
                pattern: '/^[a-zA-Z0-9._-]+$/',
                message: 'admin_user.username.invalid_chars',
            ),
        ]));

        if ($usernameViolations !== []) {
            $violations['username'] = $usernameViolations;
        }

        $roleViolations = $this->messages($this->validator->validate($command->roles, [
            new Assert\Count(min: 1, minMessage: 'admin_user.roles.min_count'),
            new Assert\All([
                new Assert\Type('string'),
                new Assert\Choice(choices: [
                    'ROLE_USER',
                    'ROLE_ADMIN',
                    'ROLE_SUPER_ADMIN',
                ]),
            ]),
        ]));

        if ($roleViolations !== []) {
            $violations['roles'] = $roleViolations;
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private function messages(ConstraintViolationListInterface $violations): array
    {
        $messages = [];

        foreach ($violations as $violation) {
            $messages[] = (string) $violation->getMessage();
        }

        return $messages;
    }
}
