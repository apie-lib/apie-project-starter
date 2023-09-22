<?php

namespace Apie\ApieProjectStarter\Frameworks;

use Apie\ApieProjectStarter\ProjectStarterCommand;
use Apie\ApieProjectStarter\ProjectStarterConfig;
use Apie\ApieProjectStarter\Render\TwigRender;
use CzProject\GitPhp\Git;
use Symfony\Component\Finder\Finder;

class LaravelSetup implements FrameworkSetupInterface
{
    const IGNORE_LIST = [
        'composer.json',
        'composer.lock',
    ];

    public function modifyComposerFileContents(array $composerJson, ProjectStarterConfig $projectStarterConfig): array
    {
        $composerJson['require']['apie/laravel-apie'] = ProjectStarterCommand::APIE_VERSION_TO_INSTALL;
        $composerJson['require']["laravel/laravel"] = "7.*|8.*|9.*|10.*";
        $composerJson['autoload']['psr-4']["App\\"] = "app/";
        unset($composerJson['require-dev']);
        return $composerJson;
    }

    public function writeFiles(string $targetPath, ProjectStarterConfig $projectStarterConfig): void
    {
        $cachePath = $targetPath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'setup';
        $examplePath = $targetPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Apie' . DIRECTORY_SEPARATOR . 'Example';
        
        $this->getFileFromGit('https://github.com/laravel/laravel.git', $targetPath);
        $render = new TwigRender(
            __DIR__ . '/../laravel',
            $cachePath
        );
        $render->renderAll($targetPath);
        if ($projectStarterConfig->includeUser) {
            $render = new TwigRender(
                __DIR__ . '/../user',
                $cachePath
            );
            $render->renderAll($examplePath);
        }
        $render = new TwigRender(
            __DIR__ . '/../example',
            $cachePath
        );
        $render->renderAll($examplePath);

        @mkdir($targetPath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache', recursive: true);
        file_put_contents(
            $targetPath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . '.gitignore',
            '!.gitignore'
        );
        chmod($targetPath . DIRECTORY_SEPARATOR . 'artisan', 0744);
    }

    private function getFileFromGit(string $gitUrl, string $targetPath): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('laravel-git');
        @mkdir($path, recursive: true);
        try {
            $git = new \CzProject\GitPhp\Git;
            $git->cloneRepository($gitUrl, $path);
            foreach (Finder::create()->files()->in($path) as $file) {
                if (in_array($file->getRelativePath(), self::IGNORE_LIST)) {
                    continue;
                }
                $targetFile = $targetPath . DIRECTORY_SEPARATOR . $file->getRelativePath() . DIRECTORY_SEPARATOR . $file->getBasename();
                @mkdir(dirname($targetFile), recursive: true);
                rename($file->getRealPath(), $targetFile);
            }
        } finally {
            system('rm -rf ' . escapeshellarg($path));
        }
    }
}
