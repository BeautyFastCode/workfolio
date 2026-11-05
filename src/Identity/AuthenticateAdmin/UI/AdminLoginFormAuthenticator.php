<?php

declare(strict_types=1);

namespace App\Identity\AuthenticateAdmin\UI;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class AdminLoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const string LOGIN_ROUTE = 'admin_login';
    public const string DASHBOARD_ROUTE = 'admin';

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator) {}

    public function authenticate(Request $request): Passport
    {
        $identifier = (string) $request->request->get('_username', '');
        $password = (string) $request->request->get('_password', '');
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        $identifier = mb_strtolower(trim($identifier));

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $identifier);

        return new Passport(
            new UserBadge($identifier),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('admin_authenticate', $csrfToken),
                new RememberMeBadge(),
            ],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if ($targetPath !== null) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate(self::DASHBOARD_ROUTE));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
