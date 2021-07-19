<?php

namespace Concrete5\Api\Version;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Concrete\Core\Foundation\Repetition\Comparator;
use Concrete5\Api\Composer\Composer;
use Illuminate\Filesystem\Filesystem;
use Doctum\Version\Version;
use Doctum\Version\VersionCollection;
use Symfony\Component\Process\Process;
use RuntimeException;

class PackagistVersonCollection extends VersionCollection
{

    protected $package = '';
    protected $loaded = false;
    protected $composer;
    protected $filesystem;

    protected $composerVersionSet;

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
        $this->composer->do(
            [
                'create-project',
                "{$this->package}:{$version->getName()}",
                '--no-progress',
                '--no-install',
                '--no-interaction',
                $dir
            ],
            function (Process $process, Composer $composer) use ($version, $dir) {
                $process->run();
                $this->composer->prepare();
            }
        );
    }

    /**
     * Resolve all the versions reported by composer
     *
     * @return array
     */
    public function composerVersions()
    {
        if (!$this->composerVersionSet) {$versions = [];
            $this->composer->do(
                [
                    'show',
                    $this->package,
                    '--no-ansi',
                    '--available',
                    '--no-interaction',
                ],
                function (Process $process) use (&$versions) {
                $process->run(function ($stream, $result) use (&$versions) {
                        if ($stream === 'out') {
                            if (preg_match('/^versions\s:\s(.+?)$/m', $result, $matches)) {
                                foreach (explode(', ', $matches[1]) as $version) {
                                    $versions[] = $version;
                                }
                            }
                        }
                    });
            });

            $this->composerVersionSet = array_filter($versions);
        }

        return $this->composerVersionSet;
    }

    /**
     * Add stable versions using composer semver
     * For example:
     * ->addFromSemver(['1.2.x', '0.9.x'])
     *
     */
    public function addFromSemver(array $constraints, bool $requireStable = true)
    {
        foreach ($this->composerVersions() as $version) {
            if ($requireStable && VersionParser::parseStability($version) === 'stable') {
                foreach ($constraints as $constraint) {
                    if (Semver::satisfies($version, $constraint)) {
                        $this->add($version);
                        break;
                    }
                }
            }
        }

        return $this;
    }

    public function addFromComposer(array $versionFilterRegularExpressions = [])
    {
        foreach ($this->composerVersions() as $version) {
            $this->handleComposerVersions($version, $versionFilterRegularExpressions);
        }

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
