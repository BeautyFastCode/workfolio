<?php

declare(strict_types=1);

namespace App\Content\Shared\Infrastructure\Persistence\Doctrine\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum BlockType: string implements TranslatableInterface
{
    case TEXT = 'text';
    case HTML = 'html';
    case TWIG = 'twig';
    case IMAGE = 'image';

    public function translationKey(): string
    {
        return match ($this) {
            self::TEXT => 'crud.block_type.text',
            self::HTML => 'crud.block_type.html',
            self::TWIG => 'crud.block_type.twig',
            self::IMAGE => 'crud.block_type.image',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->translationKey(), locale: $locale);
    }
}
