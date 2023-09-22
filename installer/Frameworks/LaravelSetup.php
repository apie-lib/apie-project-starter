<?php

namespace Apie\ApieProjectStarter\Frameworks;

use Apie\ApieProjectStarter\ProjectStarterCommand;
use Apie\ApieProjectStarter\ProjectStarterConfig;
use Apie\ApieProjectStarter\Render\TwigRender;

class LaravelSetup implements FrameworkSetupInterface
{
    public function modifyComposerFileContents(array $composerJson, ProjectStarterConfig $projectStarterConfig): array
    {
        $composerJson['require']['apie/laravel-apie'] = ProjectStarterCommand::APIE_VERSION_TO_INSTALL;
        $composerJson['require']["laravel/laravel"] = "7.*|8.*|9.*|10.*";
        $composerJson['autoload']['psr-4']["App\\"] = "app/";
        return $composerJson;
    }

    public function writeFiles(string $targetPath, ProjectStarterConfig $projectStarterConfig): void
    {
        $render = new TwigRender(
            __DIR__ . '/../laravel',
            $targetPath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'setup'
        );
        $render->renderAll($targetPath);
        chmod($targetPath . DIRECTORY_SEPARATOR . 'artisan', 0744);
    }
}
