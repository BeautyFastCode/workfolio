<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\UI;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'ContactForm', template: 'contact/submit_contact_message/components/ContactForm.html.twig')]
final class ContactForm {}
