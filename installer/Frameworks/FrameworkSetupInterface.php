<?php

namespace Apie\ApieProjectStarter\Frameworks;

interface FrameworkSetupInterface
{
    public function modifyComposerFileContents(array $composerJson, string $setup, bool $cms): array;
    public function writeFiles(string $targetPath, bool $cms): void;
}
