<?php

declare(strict_types=1);

namespace App\Identity\AuthenticateAdmin\UI;

use App\Shared\Domain\LocaleEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AdminSecurityController extends AbstractController
{
    #[Route('/admin/{_locale}/login', name: 'admin_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        $locale = $request->attributes->get('_locale', LocaleEnum::EN->value);

        $parameters = [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
            'csrf_token_intention' => 'admin_authenticate',
            'target_path' => null,
            'forgot_password_enabled' => false,
            'remember_me_enabled' => false,
            'locale' => $locale,
        ];

        return $this->render('identity/authenticate_admin/login.html.twig', $parameters);
    }

    #[Route('/admin/{_locale}/logout', name: 'admin_logout')]
    public function logout(): void
    {
        throw new \LogicException('Logout is handled by Symfony Security.');
    }
}
