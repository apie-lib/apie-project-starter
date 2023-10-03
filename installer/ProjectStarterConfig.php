<?php
namespace Apie\ApieProjectStarter;

final class ProjectStarterConfig
{
    public function __construct(
        public readonly string $setup,
        public readonly string $framework,
        public readonly bool $includeCms,
        public readonly bool $includeUser,
        public readonly bool $enable2Fa
    ) {
    }
}