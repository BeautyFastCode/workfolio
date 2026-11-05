<?php

declare(strict_types=1);

namespace App\Tests\Contact\SubmitContactMessage\UI;

use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactMessage;
use App\Contact\SubmitContactMessage\Application\ContactMessageTransaction;
use App\Shared\UI\TurboFrame;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * @internal
 *
 * @coversNothing
 */
final class ContactFormControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        new SchemaTool($this->entityManager)->createSchema($metadata);
    }

    public function test_contact_form_is_rendered_as_a_turbo_frame(): void
    {
        $this->client->request(
            'GET',
            '/en/contact-form',
            server: ['HTTP_TURBO_FRAME' => TurboFrame::CONTACT_FORM_ID],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('turbo-frame#%s', TurboFrame::CONTACT_FORM_ID));
        self::assertSelectorExists('form[action="/en/contact-form"][method="post"]');
        self::assertStringNotContainsString(
            '<!DOCTYPE html>',
            (string) $this->client->getResponse()->getContent(),
        );
    }

    public function test_valid_contact_form_is_saved_and_sends_an_email_notification(): void
    {
        $email = null;
        $notificationRequestedAfterCommit = false;
        $entityManager = $this->entityManager;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (RawMessage $message) use (
                &$email,
                &$notificationRequestedAfterCommit,
                $entityManager,
            ): void {
                self::assertFalse($entityManager->getConnection()->isTransactionActive());
                self::assertEquals(1, $entityManager->getConnection()->fetchOne(
                    'SELECT COUNT(*) FROM app_contact_message',
                ));

                $email = $message;
                $notificationRequestedAfterCommit = true;
            })
        ;
        self::getContainer()->set(MailerInterface::class, $mailer);

        $crawler = $this->client->request(
            'GET',
            '/en/contact-form',
            server: ['HTTP_TURBO_FRAME' => TurboFrame::CONTACT_FORM_ID],
        );
        $form = $crawler->selectButton('Send message')->form([
            'contact_message[fullName]' => 'Ada Lovelace',
            'contact_message[email]' => 'ada@example.com',
            'contact_message[subject]' => 'Characteristic subject',
            'contact_message[message]' => 'A deterministic contact message.',
        ]);

        $this->client->submit(
            $form,
            serverParameters: ['HTTP_TURBO_FRAME' => TurboFrame::CONTACT_FORM_ID],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('turbo-frame#%s', TurboFrame::CONTACT_FORM_ID));
        self::assertSelectorTextContains('.alert-success', 'Thank you for your message.');
        self::assertSelectorNotExists('form');
        self::assertTrue($notificationRequestedAfterCommit);

        $messages = $this->entityManager->getRepository(ContactMessage::class)->findAll();
        self::assertCount(1, $messages);

        $contactMessage = $messages[0];
        self::assertSame('Ada Lovelace', $contactMessage->getFullName());
        self::assertSame('ada@example.com', $contactMessage->getEmail());
        self::assertSame('Characteristic subject', $contactMessage->getSubject());
        self::assertSame('A deterministic contact message.', $contactMessage->getMessage());

        self::assertInstanceOf(TemplatedEmail::class, $email);
        self::assertEmailSubjectContains($email, 'New submission from the contact form');
        self::assertEmailAddressContains($email, 'From', 'app@workfolio.test');
        self::assertEmailAddressContains($email, 'To', 'contact@workfolio.test');
        self::assertSame('contact/submit_contact_message/emails/new_contact_notification.txt.twig', $email->getTextTemplate());
        self::assertSame([
            'id' => $contactMessage->getId()->toString(),
            'fullName' => 'Ada Lovelace',
            'contactEmail' => 'ada@example.com',
            'subject' => 'Characteristic subject',
            'message' => 'A deterministic contact message.',
            'createdAt' => $contactMessage->getCreatedAt(),
        ], $email->getContext());
    }

    public function test_invalid_contact_form_is_not_saved_and_does_not_send_an_email_notification(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::never())
            ->method('send')
        ;
        self::getContainer()->set(MailerInterface::class, $mailer);

        $crawler = $this->client->request(
            'GET',
            '/en/contact-form',
            server: ['HTTP_TURBO_FRAME' => TurboFrame::CONTACT_FORM_ID],
        );
        $form = $crawler->selectButton('Send message')->form([
            'contact_message[fullName]' => 'A',
            'contact_message[email]' => 'invalid-email',
            'contact_message[subject]' => 'S',
            'contact_message[message]' => 'short',
        ]);

        $this->client->submit(
            $form,
            serverParameters: ['HTTP_TURBO_FRAME' => TurboFrame::CONTACT_FORM_ID],
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists(sprintf('turbo-frame#%s', TurboFrame::CONTACT_FORM_ID));
        self::assertSelectorExists('form[name="contact_message"]');
        self::assertSelectorCount(4, '.invalid-feedback');
        self::assertStringNotContainsString(
            '<!DOCTYPE html>',
            (string) $this->client->getResponse()->getContent(),
        );
        self::assertCount(0, $this->entityManager->getRepository(ContactMessage::class)->findAll());
    }

    public function test_mailer_failure_after_commit_keeps_the_message_and_returns_success(): void
    {
        $entityManager = $this->entityManager;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function () use ($entityManager): never {
                self::assertFalse($entityManager->getConnection()->isTransactionActive());
                self::assertEquals(1, $entityManager->getConnection()->fetchOne(
                    'SELECT COUNT(*) FROM app_contact_message',
                ));

                throw new \RuntimeException('Mailer unavailable after commit.');
            })
        ;
        self::getContainer()->set(MailerInterface::class, $mailer);

        $crawler = $this->client->request(
            'GET',
            '/en/contact-form',
            server: ['HTTP_TURBO_FRAME' => TurboFrame::CONTACT_FORM_ID],
        );
        $form = $crawler->selectButton('Send message')->form([
            'contact_message[fullName]' => 'Grace Hopper',
            'contact_message[email]' => 'grace@example.com',
            'contact_message[subject]' => 'Committed message',
            'contact_message[message]' => 'This message must survive a Mailer failure.',
        ]);

        $this->client->submit(
            $form,
            serverParameters: ['HTTP_TURBO_FRAME' => TurboFrame::CONTACT_FORM_ID],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('turbo-frame#%s', TurboFrame::CONTACT_FORM_ID));
        self::assertSelectorTextContains('.alert-success', 'Thank you for your message.');
        self::assertSelectorNotExists('form');

        $this->entityManager->clear();
        $messages = $this->entityManager->getRepository(ContactMessage::class)->findAll();
        self::assertCount(1, $messages);
        self::assertSame('Grace Hopper', $messages[0]->getFullName());
        self::assertSame('grace@example.com', $messages[0]->getEmail());
        self::assertSame('Committed message', $messages[0]->getSubject());
        self::assertSame('This message must survive a Mailer failure.', $messages[0]->getMessage());
    }

    public function test_persistence_failure_does_not_request_a_notification(): void
    {
        $transaction = $this->createMock(ContactMessageTransaction::class);
        $transaction
            ->expects(self::once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Database unavailable.'))
        ;
        self::getContainer()->set(ContactMessageTransaction::class, $transaction);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');
        self::getContainer()->set(MailerInterface::class, $mailer);

        $crawler = $this->client->request(
            'GET',
            '/en/contact-form',
            server: ['HTTP_TURBO_FRAME' => TurboFrame::CONTACT_FORM_ID],
        );
        $form = $crawler->selectButton('Send message')->form([
            'contact_message[fullName]' => 'Katherine Johnson',
            'contact_message[email]' => 'katherine@example.com',
            'contact_message[subject]' => 'Persistence failure',
            'contact_message[message]' => 'This message must not trigger a notification.',
        ]);

        $this->client->submit(
            $form,
            serverParameters: ['HTTP_TURBO_FRAME' => TurboFrame::CONTACT_FORM_ID],
        );

        self::assertResponseStatusCodeSame(500);
        self::assertCount(0, $this->entityManager->getRepository(ContactMessage::class)->findAll());
    }
}
