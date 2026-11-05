<?php

declare(strict_types=1);

namespace App\Contact\ListContactLinks\UI;

use App\Contact\ListContactLinks\Application\ContactLinkView;
use App\Contact\ListContactLinks\Application\ListContactLinksHandler;
use App\Contact\ListContactLinks\Application\ListContactLinksQuery;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent(name: 'ContactData', template: 'contact/list_contact_links/components/ContactData.html.twig')]
final class ContactData
{
    /**
     * @var list<ContactLinkView>
     */
    public array $contacts = [];

    public function __construct(
        private readonly ListContactLinksHandler $handler,
    ) {}

    #[PostMount]
    public function postMount(): void
    {
        $this->contacts = ($this->handler)(new ListContactLinksQuery())->links;
    }
}
