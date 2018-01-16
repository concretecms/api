<?php

namespace Concrete5\Api\Version;

use Concrete5\Api\Composer\Composer;
use Illuminate\Filesystem\Filesystem;
use Sami\Version\Version;
use Sami\Version\VersionCollection;
use Symfony\Component\Finder\Glob;
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

    public function addFromComposer($grep = '')
    {
        $this->composer->do(function (Process $process, $composer) use ($grep) {
            $process->setCommandLine("{$composer} show {$this->package} --no-ansi --available --no-interaction")
                ->run(function ($stream, $result) use ($grep) {
                    if ($stream === 'out') {
                        if (preg_match('/^versions\s:\s(.+?)$/m', $result, $matches)) {
                            $this->handleComposerVersions($matches[1], $grep);
                        }
                    }
                });
        });

        return $this;
    }

    protected function handleComposerVersions(string $composerVersions, $grep)
    {
        $versions = array_reverse(explode(', ', $composerVersions));
        foreach ($this->filteredVersions($versions, $grep) as $version) {
            $this->add($version);
        }
    }

    protected function filteredVersions(array $versions, $filter)
    {
        if (!$filter instanceof \Closure && $filter) {
            $regexes = array();
            foreach ((array)$filter as $f) {
                $regexes[] = Glob::toRegex($f);
            }

            $filter = function ($version) use ($regexes) {
                foreach ($regexes as $regex) {
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
