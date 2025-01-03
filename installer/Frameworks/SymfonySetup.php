<?php

namespace Apie\ApieProjectStarter\Frameworks;

use Apie\ApieProjectStarter\ProjectStarterCommand;
use Apie\ApieProjectStarter\ProjectStarterConfig;
use Apie\ApieProjectStarter\Render\TwigRender;

class SymfonySetup implements FrameworkSetupInterface
{
    private const REQUIREMENTS = [
        'apie/apie-bundle' => ProjectStarterCommand::APIE_VERSION_TO_INSTALL,
        "doctrine/doctrine-bundle" => "^2.10",
        "symfony/console" => "6.*|7.*",
        'symfony/framework-bundle' => '6.*|7.*',
        'symfony/runtime' => '6.*|7.*',
        "symfony/dotenv" => "6.*|7.*",
        "symfony/flex" => "^2",
        "symfony/yaml" => "6.1.*|7.*",
    ];

    private const RECOMMENDED_REQUIREMENTS = [
        'symfony/security-bundle' => '6.*|7.*',
    ];

    private const CMS_REQUIREMENTS = [
        "apie/cms-layout-graphite" => ProjectStarterCommand::APIE_VERSION_TO_INSTALL,
        'symfony/twig-bundle' => '6.*|7.*',
    ];

    private const DEV_REQUIREMENTS = [
        "symfony/debug-bundle" => "6.*|7.*",
        "symfony/monolog-bundle" => "^3.0|7.*",
        "symfony/stopwatch" => "6.*|7.*",
        "symfony/web-profiler-bundle" => "6.*|7.*"
    ];

    public function modifyComposerFileContents(array $composerJson, ProjectStarterConfig $projectStarterConfig): array
    {
        foreach (self::REQUIREMENTS as $package => $versionConstraint) {
            $composerJson['require'][$package] = $versionConstraint;
        }
        foreach (self::DEV_REQUIREMENTS as $package => $versionConstraint) {
            $composerJson['require-dev'][$package] = $versionConstraint;
        }
        if ($projectStarterConfig->setup !== 'minimal') {
            foreach (self::RECOMMENDED_REQUIREMENTS as $package => $versionConstraint) {
                $composerJson['require'][$package] = $versionConstraint;
            }
        }
        if ($projectStarterConfig->includeCms) {
            foreach (self::CMS_REQUIREMENTS as $package => $versionConstraint) {
                $composerJson['require'][$package] = $versionConstraint;
            }
        }
        $composerJson['autoload']['psr-4']["App\\"] = "src/";
        $composerJson['config'] = [
            "allow-plugins" => [
                "apie/apie-common-plugin" => true,
                "symfony/runtime" => true,
                "symfony/flex" => true
            ],
        ];
        $composerJson['conflict']['symfony/twig-bundle'] = "v6.4.0-BETA1";
        return $composerJson;
    }

    public function writeFiles(string $targetPath, ProjectStarterConfig $projectStarterConfig): void
    {
        $cachePath = $targetPath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'setup';
        $examplePath = $targetPath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Apie' . DIRECTORY_SEPARATOR . 'Example';
        $render = new TwigRender(
            __DIR__ . '/../symfony',
            $cachePath,
            $projectStarterConfig
        );
        $render->renderAll($targetPath);
        $render = new TwigRender(
            __DIR__ . '/../example',
            $cachePath,
            $projectStarterConfig
        );
        $render->renderAll($examplePath);
        chmod($targetPath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console', 0744);
        if ($projectStarterConfig->includeCms) {
            $render = new TwigRender(
                __DIR__ . '/../symfony-cms',
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
        system('rm -rf ' . escapeshellarg($targetPath . '/var/cache/setup'));
    }
}
