<?php

namespace Apie\ApieProjectStarter;

use Apie\ApieProjectStarter\Frameworks\FrameworkSetupInterface;
use Apie\ApieProjectStarter\Frameworks\LaravelSetup;
use Apie\ApieProjectStarter\Frameworks\SymfonySetup;
use Composer\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Dotenv\Dotenv;

class ProjectStarterCommand extends Command
{
    public const APIE_VERSION_TO_INSTALL = '1.0.0.x-dev';

    protected function configure()
    {
        $dotenv = new Dotenv();
        $dotenv->usePutenv(true);
        $paths = [getcwd() . '/.env', __DIR__.'/.env', __DIR__.'/../.env', __DIR__.'/../../.env', __DIR__.'/../../../.env'];
        foreach ($paths as $path) {
            if (is_readable($path)) {
                $dotenv->load($path);
            }
        }
        $this->setName('start-project')
            ->setDescription('Start a new project with options')
            ->addOption(
                'setup',
                $_ENV['APIE_STARTER_SETUP'] ?? null,
                InputArgument::OPTIONAL,
                'Project setup (minimal/preferred/maximum)'
            )
            ->addOption(
                'cms',
                $_ENV['APIE_STARTER_ENABLE_CMS'] ?? null,
                InputArgument::OPTIONAL,
                'Enable CMS'
            )
            ->addOption(
                'framework',
                $_ENV['APIE_STARTER_FRAMEWORK'] ?? null,
                InputArgument::OPTIONAL,
                'Framework (Laravel/Symfony)'
            )
            ->addOption(
                'user-object',
                $_ENV['APIE_STARTER_ENABLE_USER'] ?? null,
                InputArgument::OPTIONAL,
                'Default user object (yes/no)'
            )
            ->addOption(
                'enable-2fa',
                $_ENV['APIE_STARTER_ENABLE_2FA'] ?? null,
                InputArgument::OPTIONAL,
                'Enable 2FA for default user (yes/no)'
            );
    }

    private function fromOptions(mixed $value, array $allowedOptions): mixed
    {
        if (is_string($value)) {
            if (in_array($value, $allowedOptions, true)) {
                return $value;
            }
        }
        return null;
    }

    private function fromBoolean(mixed $value): mixed
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        var_dump($input->getOption('setup'));
        // Check if options are provided, otherwise, ask interactively
        $setup = $this->fromOptions($input->getOption('setup'), ['minimal', 'preferred', 'maximum']);
        $cms = $this->fromBoolean($input->getOption('cms'));
        $framework = $this->fromOptions($input->getOption('framework'), ['Symfony', 'Laravel']);
        $userObject = $this->fromBoolean($input->getOption('user-object'));
        $enable2Fa = $this->fromBoolean($input->getOption('enable-2fa'));

        $composerJson = [
            "name" => 'vendor/' . basename(realpath(__DIR__ . '/../')),
            "description" => "This project was created with apie/apie-project-starter",
            "license" => "proprietary",
            "keywords" => ["apie", "rest", "api", "openapi"],
            "minimum-stability" => "dev",
            "prefer-stable" => true,
            "require" => [],
            "require-dev" => [
                "apie/apie-phpstan-rules" => self::APIE_VERSION_TO_INSTALL,
                "phpstan/phpstan" => '^1.8.2',
                "phpunit/phpunit" => "^9.5",
            ],
            "autoload" => [
            ],
            "autoload-dev" => [
                "psr-4" => [
                    'App\Tests\\' =>  'tests/'
                ]
            ]
        ];

        if (!$setup) {
            $setupQuestion = new ChoiceQuestion(
                'Select the project setup (minimal/preferred/maximum): ',
                ['minimal', 'recommended', 'maximum'],
                'recommended'
            );
            $setup = $helper->ask($input, $output, $setupQuestion);
        }
        if ($cms === null) {
            $cmsQuestion = new ConfirmationQuestion(
                'Enable apie/cms?',
                true
            );
            $cms = $helper->ask($input, $output, $cmsQuestion);
        }

        if (!$framework) {
            $frameworkQuestion = new ChoiceQuestion(
                'Select the framework (Laravel/Symfony): ',
                ['Laravel', 'Symfony'],
                'Symfony'
            );
            $framework = $helper->ask($input, $output, $frameworkQuestion);
        }

        if ($userObject === null) {
            $userQuestion = new ConfirmationQuestion('Do you want a default user object? (yes/no): ', true);
            $userObject = $helper->ask($input, $output, $userQuestion);
        }
        if ($userObject && $enable2Fa === null) {
            $userQuestion = new ConfirmationQuestion('Do you want to enable 2FA for authentication? (yes/no): ', false);
            $enable2Fa = $helper->ask($input, $output, $userQuestion);
        }

        $output->writeln("Project setup: $setup");
        $output->writeln('Apie CMS: ' . ($cms ? 'yes' : 'no'));
        $output->writeln("Framework: $framework");
        $output->writeln("Default user object: " . ($userObject ? 'yes' : 'no'));
        $projectConfig = new ProjectStarterConfig($setup, $framework, $cms, $userObject, $enable2Fa ?? false);

        $frameworkSetup = $this->getFrameworkSetup($projectConfig);
        $composerJson['require']['apie/meta-' . $setup] = self::APIE_VERSION_TO_INSTALL;
        $composerJson = $frameworkSetup->modifyComposerFileContents($composerJson, $projectConfig);
        if ($enable2Fa) {
            $composerJson['require']['apie/otp-value-objects'] = self::APIE_VERSION_TO_INSTALL;
        }

        $output->writeln(json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents(Factory::getComposerFile(), json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $frameworkSetup->writeFiles(dirname(Factory::getComposerFile()), $projectConfig);
        $this->cleanStarterCode();

        return Command::SUCCESS;
    }

    private function cleanStarterCode(): void
    {
        unlink(__DIR__ . '/../bin/start-project');
        unlink(__DIR__ . '/../makefile');
        unlink(__DIR__ . '/../packages.json');
        system('rm -rf ' . escapeshellarg(__DIR__ . '/../installer'));

    }

    private function getFrameworkSetup(ProjectStarterConfig $config): FrameworkSetupInterface
    {
        if ($config->framework === 'Symfony') {
            return new SymfonySetup();
        }

        return new LaravelSetup();
    }

}
