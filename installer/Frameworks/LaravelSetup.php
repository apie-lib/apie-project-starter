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
        '/composer.json',
        '/composer.lock',
    ];

    public function modifyComposerFileContents(array $composerJson, ProjectStarterConfig $projectStarterConfig): array
    {
        $composerJson['keywords'][] = 'laravel';
        $composerJson['keywords'][] = 'framework';

        $composerJson['require']['apie/laravel-apie'] = ProjectStarterCommand::APIE_VERSION_TO_INSTALL;
        $composerJson['require']["laravel/framework"] = "^10.10";
        $composerJson['require']['laravel/sanctum'] = '^3.2';
        $composerJson['require']['guzzlehttp/guzzle'] = '^7.2';

        $composerJson['autoload']['psr-4']["App\\"] = "app/";
        $composerJson['autoload']['psr-4']["Database\\Factories\\"] = "database/factories/";
        $composerJson['autoload']['psr-4']["Database\\Seeders\\"] = "database/seeders/";
        
        $composerJson['require-dev']['nunomaduro/collision'] = "^7.0";
        $composerJson['require-dev']['spatie/laravel-ignition'] = '^2.0';
        $composerJson['require-dev']['nunomaduro/larastan'] = '^2.0';

        $composerJson['scripts'] = [
            "post-autoload-dump" => [
                "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
                "@php artisan package:discover --ansi"
            ],
            "post-update-cmd" => [
                "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
            ]
        ];

        $composerJson['config'] = [
            "optimize-autoloader" => true,
            "preferred-install" => "dist",
            "sort-packages" => true,
        ];

        $composerJson['extra'] = [
            "laravel" => ["dont-discover" => []]
        ];
        return $composerJson;
    }

    public function writeFiles(string $targetPath, ProjectStarterConfig $projectStarterConfig): void
    {
        $cachePath = $targetPath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'setup';
        $examplePath = $targetPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Apie' . DIRECTORY_SEPARATOR . 'Example';
        
        $this->getFileFromGit('https://github.com/laravel/laravel.git', $targetPath);
        $render = new TwigRender(
            __DIR__ . '/../laravel',
            $cachePath,
            $projectStarterConfig
        );
        $render->renderAll($targetPath);
        if ($projectStarterConfig->includeCms) {
            $render = new TwigRender(
                __DIR__ . '/../laravel-cms',
                $cachePath,
                $projectStarterConfig
            );
            $render->renderAll($targetPath);
        }
        if ($projectStarterConfig->includeUser) {
            $render = new TwigRender(
                __DIR__ . '/../user',
                $cachePath,
                $projectStarterConfig
            );
            $render->renderAll($examplePath);
        }
        $render = new TwigRender(
            __DIR__ . '/../example',
            $cachePath,
            $projectStarterConfig
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
            $git = new Git;
            $git->cloneRepository($gitUrl, $path);
            foreach (Finder::create()->files()->in($path) as $file) {
                $targetFile = $file->getRelativePath() . DIRECTORY_SEPARATOR . $file->getBasename();
                if (in_array($targetFile, self::IGNORE_LIST)) {
                    continue;
                }
                $targetFile = $targetPath . DIRECTORY_SEPARATOR . $targetFile;
                @mkdir(dirname($targetFile), recursive: true);
                rename($file->getRealPath(), $targetFile);
            }
        } finally {
            system('rm -rf ' . escapeshellarg($path));
        }
    }
}
