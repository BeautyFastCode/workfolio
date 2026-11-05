<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Validation;

use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslationInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueTranslationLocalesValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueTranslationLocales) {
            throw new UnexpectedTypeException($constraint, UniqueTranslationLocales::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof Collection && !is_array($value)) {
            return;
        }

        $seenLocales = [];

        foreach ($value as $index => $translation) {
            if (!$translation instanceof TranslationInterface) {
                continue;
            }

            $locale = $translation->getLocale()->value;

            if (isset($seenLocales[$locale])) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->setParameter('{{ locale }}', strtoupper($locale))
                    ->atPath(sprintf('[%s].locale', $index))
                    ->addViolation()
                ;

                continue;
            }

            $seenLocales[$locale] = true;
        }
    }
}
