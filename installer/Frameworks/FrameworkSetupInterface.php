<?php

namespace Apie\ApieProjectStarter\Frameworks;

use Apie\ApieProjectStarter\ProjectStarterConfig;

interface FrameworkSetupInterface
{
    public function modifyComposerFileContents(array $composerJson, ProjectStarterConfig $projectStarterConfig): array;
    public function writeFiles(string $targetPath, ProjectStarterConfig $projectStarterConfig): void;
}
