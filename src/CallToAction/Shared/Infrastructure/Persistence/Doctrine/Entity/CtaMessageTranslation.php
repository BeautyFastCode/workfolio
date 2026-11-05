<?php

declare(strict_types=1);

namespace App\CallToAction\Shared\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Behavior\TimestampableTrait;
use App\Shared\Infrastructure\Persistence\Doctrine\Entity\BaseEntity;
use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslatableInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslationInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\Translation\TranslationTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements TranslationInterface<CtaMessage>
 */
#[ORM\Entity]
#[ORM\Table(name: 'app_cta_message_translation')]
#[ORM\UniqueConstraint(
    name: 'uniq_cta_message_translation',
    columns: ['cta_message_id', 'locale']
)]
class CtaMessageTranslation extends BaseEntity implements TranslationInterface, TimestampableInterface
{
    /**
     * @use TranslationTrait<CtaMessage>
     */
    use TranslationTrait;
    use TimestampableTrait;

    #[ORM\Column(name: 'message', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 255)]
    private string $message = '';

    /**
     * @var CtaMessage
     */
    #[ORM\ManyToOne(targetEntity: CtaMessage::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'cta_message_id', nullable: false, onDelete: 'CASCADE')]
    private TranslatableInterface $translatable;

    public function __construct()
    {
        parent::__construct();

        $this->initializeCreatedAt();
    }

    public function __toString(): string
    {
        $locale = strtoupper($this->locale->value);
        $message = $this->message ?: sprintf(
            'Translation #%s',
            $this->id->toString()
        );

        return sprintf('%s: %s', $locale, $message);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
