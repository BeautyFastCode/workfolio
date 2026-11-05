<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\UI;

use App\Identity\CreateAdminUser\Application\AdminUserPersistenceFailed;
use App\Identity\CreateAdminUser\Application\CreateAdminUserCommand as CreateAdminUser;
use App\Identity\CreateAdminUser\Application\CreateAdminUserHandler;
use App\Identity\CreateAdminUser\Application\InvalidAdminUser;
use App\Identity\Shared\Application\AdminUserRoleEnum;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:admin-user:create',
    description: 'Create a new admin user',
)]
final class AdminUserCreateCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly CreateAdminUserHandler $handler,
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->isInteractive()) {
            $this->io->error('This command must be run interactively.');

            return Command::FAILURE;
        }

        $this->io->title('Admin user creation');

        $command = $this->askAdminUserData();

        if (!$command instanceof CreateAdminUser) {
            $this->io->error('Incorrect data provided.');

            return Command::INVALID;
        }

        $this->showSummary($command);

        if (!$this->adminCreationConfirmed()) {
            $this->io->error('Admin user creation has been aborted.');

            return Command::INVALID;
        }

        try {
            $result = ($this->handler)($command);
        } catch (InvalidAdminUser $exception) {
            foreach ($exception->violations as $field => $messages) {
                foreach ($messages as $message) {
                    $this->io->error(sprintf('%s: %s', $field, $message));
                }
            }

            return Command::FAILURE;
        } catch (AdminUserPersistenceFailed $exception) {
            $message = $exception->getPrevious()?->getMessage() ?? $exception->getMessage();
            $this->io->error('Failed to create admin user: '.$message);

            return Command::INVALID;
        }

        if (!$result->wasCreated()) {
            foreach ($result->conflicts as $conflict) {
                $this->io->error(sprintf(
                    '%s: This value is already used.',
                    $conflict->value,
                ));
            }

            return Command::FAILURE;
        }

        $this->io->success('Admin user has been successfully created.');

        return Command::SUCCESS;
    }

    private function askAdminUserData(): ?CreateAdminUser
    {
        $email = $this->io->askQuestion($this->createEmailQuestion());
        $username = $this->io->askQuestion($this->createWithNotNullValidator('Username'));
        $plainPassword = $this->io->askQuestion($this->createWithNotNullValidator('Password', true));
        $roles = $this->createRoleChoices();

        if (!is_string($email) || !is_string($username) || !is_string($plainPassword)) {
            return null;
        }

        return new CreateAdminUser($email, $username, $plainPassword, $roles);
    }

    private function createEmailQuestion(): Question
    {
        $question = new Question('Email');
        $question->setValidator(function (mixed $email): string {
            if (!is_string($email) || filter_var($email, \FILTER_VALIDATE_EMAIL) === false) {
                throw new \InvalidArgumentException('The email address provided is invalid. Please try again.');
            }

            return $email;
        });
        $question->setMaxAttempts(3);

        return $question;
    }

    private function createWithNotNullValidator(string $askedQuestion, bool $hidden = false): Question
    {
        $question = new Question($askedQuestion);
        $question->setValidator(function (mixed $value): string {
            if (!is_string($value)) {
                throw new \InvalidArgumentException('The value cannot be empty.');
            }

            return $value;
        });
        $question->setMaxAttempts(3);
        $question->setHidden($hidden);

        return $question;
    }

    /**
     * @return list<string>
     */
    private function createRoleChoices(): array
    {
        $this->io->note('Choose one or more, separated by commas, e.g. 0,2.');

        $choices = [];
        foreach (AdminUserRoleEnum::cases() as $role) {
            $choices[] = $role->value;
        }

        $selectedRoles = $this->io->choice(
            'Roles',
            $choices,
            multiSelect: true,
        );

        if (!is_array($selectedRoles)) {
            throw new \LogicException('Expected selected roles to be an array.');
        }

        $roles = [];
        foreach ($selectedRoles as $selectedRole) {
            if (!is_string($selectedRole)) {
                continue;
            }

            $roles[] = AdminUserRoleEnum::from($selectedRole)->value;
        }

        return $roles;
    }

    private function showSummary(CreateAdminUser $command): void
    {
        $this->io->writeln('The following admin user will be created:');

        $this->io->table(
            [
                'Email', 'Username', 'Roles',
            ],
            [
                [
                    $command->email,
                    $command->username,
                    implode(', ', $command->roles),
                ],
            ],
        );
    }

    private function adminCreationConfirmed(): bool
    {
        return $this->io->confirm('Do you want to save this admin user?');
    }
}
