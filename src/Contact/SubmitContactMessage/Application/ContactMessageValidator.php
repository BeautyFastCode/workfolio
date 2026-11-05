<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Application;

final class ContactMessageValidator
{
    /**
     * @return array<string, list<string>>
     */
    public function validate(SubmitContactMessageCommand $command): array
    {
        $violations = [];

        $this->validateRequiredLength($violations, 'fullName', $command->fullName, 2, 150);
        $this->validateEmail($violations, $command->email);
        $this->validateRequiredLength($violations, 'subject', $command->subject, 2, 200);
        $this->validateRequiredLength($violations, 'message', $command->message, 10, 5000);

        return $violations;
    }

    /**
     * @param array<string, list<string>> $violations
     */
    private function validateEmail(array &$violations, string $email): void
    {
        if (trim($email) === '') {
            $violations['email'][] = 'required';

            return;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $violations['email'][] = 'invalid';
        }

        if ($this->length($email) > 180) {
            $violations['email'][] = 'too_long';
        }
    }

    /**
     * @param array<string, list<string>> $violations
     */
    private function validateRequiredLength(
        array &$violations,
        string $field,
        string $value,
        int $minimum,
        int $maximum,
    ): void {
        if (trim($value) === '') {
            $violations[$field][] = 'required';

            return;
        }

        $length = $this->length($value);

        if ($length < $minimum) {
            $violations[$field][] = 'too_short';
        }

        if ($length > $maximum) {
            $violations[$field][] = 'too_long';
        }
    }

    private function length(string $value): int
    {
        $length = iconv_strlen($value, 'UTF-8');

        return $length === false ? strlen($value) : $length;
    }
}
