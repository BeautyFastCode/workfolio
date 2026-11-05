<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Validation;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueTranslationLocales extends Constraint
{
    public string $message = 'translatable.translations.unique';
}
