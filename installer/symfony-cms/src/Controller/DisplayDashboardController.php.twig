<?php
namespace App\Controller;

use Apie\ApieBundle\Wrappers\BoundedContextSelected;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class DisplayDashboardController
{
    public function __construct(
        private readonly BoundedContextSelected $boundedContextSelected,
        private readonly Environment $twig
    ) {
    }

    public function __invoke(): Response
    {
        return new Response($this->twig->render($this->determineTemplate(), []));
    }

    private function determineTemplate(): string
    {
        $boundedContext = $this->boundedContextSelected->getBoundedContextFromRequest();
        if ($boundedContext) {
            $contextTemplate = 'apie/' . $boundedContext->getId()->toNative() . '.html.twig';
            if ($this->twig->getLoader()->exists($contextTemplate)) {
                return $contextTemplate;
            }
        }
        return 'apie/general_text.html.twig';
    }
}