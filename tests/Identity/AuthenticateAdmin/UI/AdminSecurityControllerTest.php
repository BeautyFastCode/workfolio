<?php

declare(strict_types=1);

namespace App\Tests\Identity\AuthenticateAdmin\UI;

use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class AdminSecurityControllerTest extends WebTestCase
{
    private const string PASSWORD = 'Characteristic123!';

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

    public function test_anonymous_user_is_redirected_to_login_page(): void
    {
        $this->client->request('GET', '/admin/en');

        self::assertResponseRedirects('/admin/en/login');

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[method="post"] input[name="_username"]');
        self::assertSelectorExists('form[method="post"] input[name="_password"]');
    }

    public function test_valid_credentials_log_administrator_in(): void
    {
        $this->createAdministrator();

        $crawler = $this->client->request('GET', '/admin/en/login');
        $form = $crawler->filter('form')->form([
            '_username' => ' ADMIN@EXAMPLE.COM ',
            '_password' => self::PASSWORD,
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/admin/en');

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Dashboard');
    }

    public function test_username_can_be_used_instead_of_email(): void
    {
        $this->createAdministrator();

        $crawler = $this->client->request('GET', '/admin/en/login');
        $form = $crawler->filter('form')->form([
            '_username' => ' ADMIN ',
            '_password' => self::PASSWORD,
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/admin/en');
    }

    public function test_invalid_credentials_redirect_back_to_login_with_error(): void
    {
        $this->createAdministrator();

        $crawler = $this->client->request('GET', '/admin/en/login');
        $form = $crawler->filter('form')->form([
            '_username' => ' ADMIN@EXAMPLE.COM ',
            '_password' => 'invalid-password',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/admin/en/login');

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.alert-danger');
        self::assertInputValueSame('_username', 'admin@example.com');
    }

    public function test_logged_in_administrator_can_access_dashboard(): void
    {
        $administrator = $this->createAdministrator();
        $this->client->loginUser($administrator, 'admin');

        $this->client->request('GET', '/admin/en');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Dashboard');
    }

    public function test_logout_ends_session_and_protects_dashboard_again(): void
    {
        $administrator = $this->createAdministrator();
        $this->client->loginUser($administrator, 'admin');

        $this->client->request('GET', '/admin/en/logout');

        self::assertResponseRedirects('/admin/en/login');

        $this->client->request('GET', '/admin/en');

        self::assertResponseRedirects('/admin/en/login');
    }

    private function createAdministrator(): AdminUser
    {
        $administrator = new AdminUser();
        $administrator->setEmail('admin@example.com');
        $administrator->setUsername('admin');
        $administrator->setRoles(['ROLE_ADMIN']);
        $administrator->setEnabled(true);

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $passwordHasher);
        $administrator->setPassword($passwordHasher->hashPassword($administrator, self::PASSWORD));

        $this->entityManager->persist($administrator);
        $this->entityManager->flush();

        return $administrator;
    }
}
