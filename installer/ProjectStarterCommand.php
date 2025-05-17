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

    protected function configure(): void
    {
        $this->setName('start-project')
            ->setDescription('Start a new project with options')
            ->addOption(
                'setup',
                null,
                InputArgument::OPTIONAL,
                'Project setup (minimal/preferred/maximum)'
            )
            ->addOption(
                'cms',
                null,
                InputArgument::OPTIONAL,
                'Enable CMS'
            )
            ->addOption(
                'framework',
                null,
                InputArgument::OPTIONAL,
                'Framework (Laravel/Symfony)'
            )
            ->addOption(
                'user-object',
                null,
                InputArgument::OPTIONAL,
                'Default user object (yes/no)'
            )
            ->addOption(
                'enable-2fa',
                null,
                InputArgument::OPTIONAL,
                'Enable 2FA for default user (yes/no)'
            );
    }

    private function fromOptions(mixed $value, string $environmentVariable, array $allowedOptions): mixed
    {
        if ($value === null) {
            $value = getenv($environmentVariable);
        }
        if (is_string($value)) {
            if (in_array($value, $allowedOptions, true)) {
                return $value;
            }
        }
        return null;
    }

    private function fromBoolean(mixed $value, string $environmentVariable): mixed
    {
        if ($value === null) {
            $value = getenv($environmentVariable);
            if ($value === false) {
                return null;
            }
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dotenv = new Dotenv();
        $dotenv->usePutenv(true);
        $paths = [getcwd() . '/.env', __DIR__.'/.env', __DIR__.'/../.env', __DIR__.'/../../.env', __DIR__.'/../../../.env'];
        foreach ($paths as $path) {
            if (is_readable($path)) {
                $dotenv->load($path);
            }
        }
        $helper = $this->getHelper('question');

        // Check if options are provided, otherwise, ask interactively
        $setup = $this->fromOptions($input->getOption('setup'), 'APIE_STARTER_SETUP', ['minimal', 'preferred', 'maximum']);
        $cms = $this->fromBoolean($input->getOption('cms'), 'APIE_STARTER_ENABLE_CMS');
        $framework = $this->fromOptions($input->getOption('framework'), 'APIE_STARTER_FRAMEWORK', ['Symfony', 'Laravel']);
        $userObject = $this->fromBoolean($input->getOption('user-object'), 'APIE_STARTER_ENABLE_USER');
        $enable2Fa = $this->fromBoolean($input->getOption('enable-2fa'), 'APIE_STARTER_ENABLE_2FA');

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
                "phpstan/phpstan" => '^2.0',
                "phpunit/phpunit" => "^11.0",
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
