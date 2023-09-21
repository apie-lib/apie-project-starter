<?php

namespace Apie\ApieProjectStarter\Frameworks;

use Apie\ApieProjectStarter\ProjectStarterCommand;
use Apie\ApieProjectStarter\Render\TwigRender;

class LaravelSetup implements FrameworkSetupInterface
{
    public function modifyComposerFileContents(array $composerJson, string $setup, bool $cms): array
    {
        $composerJson['require']['apie/laravel-apie'] = ProjectStarterCommand::APIE_VERSION_TO_INSTALL;
        $composerJson['require']["laravel/laravel"] = "7.*|8.*|9.*|10.*";
        $composerJson['autoload']['psr-4']["App\\"] = "app/";
        return $composerJson;
    }

    public function writeFiles(string $targetPath, bool $cms): void
    {
        $render = new TwigRender(
            __DIR__ . '/../laravel',
            $targetPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'setup'
        );
        $render->renderAll($targetPath);
    }
}
