<?php

namespace Concrete5\Api\Version;

use Concrete5\Api\Composer\Composer;
use Illuminate\Filesystem\Filesystem;
use Sami\Version\Version;
use Sami\Version\VersionCollection;
use Symfony\Component\Process\Process;
use RuntimeException;

class PackagistVersonCollection extends VersionCollection
{

    protected $package = '';
    protected $loaded = false;
    protected $composer;
    protected $filesystem;

    public function __construct($composerHandle, Composer $composer, Filesystem $filesystem)
    {
        $this->package = $composerHandle;
        $this->composer = $composer;
        $this->filesystem = $filesystem;
        parent::__construct([]);
    }

    protected function switchVersion(Version $version)
    {
        $dir = $this->composer->getWorkingPath();
        for ($cycle = 0; $cycle < 10 && $this->filesystem->isDirectory($dir); $cycle++) {
            $this->filesystem->deleteDirectory($dir);
        }
        if ($this->filesystem->isDirectory($dir)) {
            throw new RuntimeException("Failed to delete directory {$dir}");
        }
        if (!$this->filesystem->makeDirectory($dir)) {
            throw new RuntimeException("Failed to create directory {$dir}");
        }
        $this->composer->do(function (Process $process, $composer) use ($version, $dir) {
            $process->setCommandLine(
                "{$composer} create-project {$this->package}:{$version->getName()} --no-progress --no-install --no-interaction {$dir}"
            )->run();
            $this->composer->prepare();
        });
    }

    public function addFromComposer(array $versionFilterRegularExpressions = [])
    {
        $this->composer->do(function (Process $process, $composer) use ($versionFilterRegularExpressions) {
            $process->setCommandLine("{$composer} show {$this->package} --no-ansi --available --no-interaction")
                ->run(function ($stream, $result) use ($versionFilterRegularExpressions) {
                    if ($stream === 'out') {
                        if (preg_match('/^versions\s:\s(.+?)$/m', $result, $matches)) {
                            $this->handleComposerVersions($matches[1], $versionFilterRegularExpressions);
                        }
                    }
                });
        });

        return $this;
    }

    protected function handleComposerVersions(string $composerVersions, $versionFilterRegularExpressions)
    {
        $versions = array_reverse(explode(', ', $composerVersions));
        foreach ($this->filteredVersions($versions, $versionFilterRegularExpressions) as $version) {
            $this->add($version);
        }
    }

    protected function filteredVersions(array $versions, $filter)
    {
        if (!$filter instanceof \Closure && $filter) {
            $filter = function ($version) use ($filter) {
                foreach ((array) $filter as $regex) {
                    if (preg_match($regex, $version)) {
                        return true;
                    }
                }

                return false;
            };
        }

        foreach ($versions as $version) {
            if (!$filter || $filter($version)) {
                yield $version;
            }
        }

    }

}
