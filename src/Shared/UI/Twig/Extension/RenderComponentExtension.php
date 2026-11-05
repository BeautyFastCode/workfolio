<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig\Extension;

use Twig\Attribute\AsTwigFunction;
use Twig\Environment;

final readonly class RenderComponentExtension
{
    public function __construct(
        private Environment $twig
    ) {}

    #[AsTwigFunction('render_component')]
    public function renderComponent(string $shortCode): string
    {
        $name = preg_replace('/^\[\[(.*?)]]$/', '$1', $shortCode);

        return $this->twig->render('shared/twig/_render_component_wrapper.html.twig', [
            'name' => $name,
        ]);
    }
}
