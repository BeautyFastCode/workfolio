<?php

declare(strict_types=1);

namespace App\Content\ShowPublishedPage\UI;

use App\Content\ShowPublishedPage\Application\PublishedPageView;
use App\Content\ShowPublishedPage\Application\ShowPublishedPageHandler;
use App\Content\ShowPublishedPage\Application\ShowPublishedPageQuery;
use App\Shared\Domain\LocaleEnum;
use App\Shared\UI\TurboFrame;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home_root')]
    public function noLocale(): RedirectResponse
    {
        return $this->redirectToRoute('app_home', ['_locale' => LocaleEnum::EN->value]);
    }

    #[Route('/{_locale<%app.supported_locales%>}/{slug}', name: 'app_home', defaults: ['slug' => null])]
    public function index(
        ?string $slug,
        Request $request,
        ShowPublishedPageHandler $handler,
    ): Response {
        $page = $handler(new ShowPublishedPageQuery($slug, $request->getLocale()));

        if (!$page instanceof PublishedPageView) {
            throw $this->createNotFoundException();
        }

        if ($request->headers->get('Turbo-Frame') === TurboFrame::PAGE_ID) {
            return $this->render('content/show_published_page/_content.html.twig', ['page' => $page]);
        }

        return $this->render('content/show_published_page/index.html.twig', ['page' => $page]);
    }
}
