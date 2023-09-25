<?php

namespace Apie\ApieProjectStarter\Render;

use Apie\ApieProjectStarter\ProjectStarterCommand;
use Apie\Core\ApieLib;
use Symfony\Component\Finder\Finder;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigRender
{
    private Environment $twig;

    public function __construct(private readonly string $templatePath, private readonly string $cachePath)
    {
        $loader = new FilesystemLoader($this->templatePath);
        $this->twig = new Environment($loader, [
            'cache' => $this->cachePath,
        ]);
    }

    public function renderAll(string $targetPath): void
    {
        foreach (Finder::create()->files()->name('/.+.twig/')->ignoreDotFiles(false)->in($this->templatePath) as $file) {
            $templateFile = $file->getRelativePath() . DIRECTORY_SEPARATOR . $file->getBasename('.twig');
            $targetFile = $targetPath . DIRECTORY_SEPARATOR . $templateFile;
            @mkdir(dirname($targetFile), recursive: true);
            file_put_contents(
                $targetFile,
                $this->twig->render($templateFile . '.twig', ['apieVersion' => ProjectStarterCommand::APIE_VERSION_TO_INSTALL])
            );
        }
    }
}
