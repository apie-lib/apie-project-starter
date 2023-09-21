<?php

namespace Apie\ApieProjectStarter\Frameworks;

use Apie\ApieProjectStarter\ProjectStarterCommand;
use Apie\ApieProjectStarter\Render\TwigRender;

class SymfonySetup implements FrameworkSetupInterface
{
    private const REQUIREMENTS = [
        'apie/apie-bundle' => ProjectStarterCommand::APIE_VERSION_TO_INSTALL,
        "doctrine/doctrine-bundle" => "^2.10",
        "symfony/console" => "6.*",
        'symfony/framework-bundle' => '6.*',
        'symfony/runtime' => '6.*',
        "symfony/dotenv" => "6.*",
        "symfony/flex" => "^2",
        "symfony/yaml" => "6.1.*",
    ];

    private const RECOMMENDED_REQUIREMENTS = [
        'symfony/security-bundle' => '6.*',
    ];

    private const CMS_REQUIREMENTS = [
        "apie/cms-layout-graphite" => ProjectStarterCommand::APIE_VERSION_TO_INSTALL,
        'symfony/twig-bundle' => '6.*',
    ];

    private const DEV_REQUIREMENTS = [
        "symfony/debug-bundle" => "6.*",
        "symfony/monolog-bundle" => "^3.0",
        "symfony/stopwatch" => "6.*",
        "symfony/web-profiler-bundle" => "6.*"
    ];

    public function modifyComposerFileContents(array $composerJson, string $setup, bool $cms): array
    {
        foreach (self::REQUIREMENTS as $package => $versionConstraint) {
            $composerJson['require'][$package] = $versionConstraint;
        }
        foreach (self::DEV_REQUIREMENTS as $package => $versionConstraint) {
            $composerJson['require-dev'][$package] = $versionConstraint;
        }
        if ($setup !== 'minimal') {
            foreach (self::RECOMMENDED_REQUIREMENTS as $package => $versionConstraint) {
                $composerJson['require'][$package] = $versionConstraint;
            }
        }
        if ($cms) {
            foreach (self::CMS_REQUIREMENTS as $package => $versionConstraint) {
                $composerJson['require'][$package] = $versionConstraint;
            }
        }
        $composerJson['autoload']['psr-4']["App\\"] = "src/";
        $composerJson['config'] = [
            "allow-plugins" => [
                "symfony/runtime" => true,
                "symfony/flex" => true
            ],
        ];
        return $composerJson;
    }

    public function writeFiles(string $targetPath, bool $cms): void
    {
        $cachePath = $targetPath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'setup';
        $render = new TwigRender(
            __DIR__ . '/../symfony',
            $cachePath
        );
        $render->renderAll($targetPath);
        $render = new TwigRender(
            __DIR__ . '/../example',
            $cachePath
        );
        $render->renderAll($targetPath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Apie' . DIRECTORY_SEPARATOR . 'Example');
        chmod($targetPath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console', 0744);
        if ($cms) {
            $render = new TwigRender(
                __DIR__ . '/../symfony-cms',
                $cachePath
            );
            $render->renderAll($targetPath);
        }
        system('rm -rf ' . escapeshellarg($targetPath . '/var/cache/setup'));
    }
}
