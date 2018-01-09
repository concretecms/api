<?php

namespace Concrete5\Api\Composer\Script;

use Composer\Script\Event;
use RuntimeException;

class NewVersionsAvailable
{
    /**
     * @param Event $event
     *
     * @throws RuntimeException
     *
     * @return int
     */
    public static function run(Event $event): int
    {
        $missingVersions = static::getMissingVersions();
        if (count($missingVersions) === 0) {
            throw new RuntimeException('No version need to be built.');
        }
        $event->getIO()->write('# Missing versions');
        foreach ($missingVersions as $missingVersion) {
            $event->getIO()->write($missingVersion);
        }
        return 0;
    }

    /**
     * @return string[]
     */
    protected static function getMissingVersions(): array
    {
        $result = [];
        $config = require dirname(__DIR__, 3) . '/sami_config.php';
        $buildDir = $config['build_dir'];
        $project = $config['project'];
        foreach ($project->getVersions() as $version) {
            if (!is_dir(str_replace('%version%', $version->getName(), $buildDir))) {
                $result[] = $version->getName();
            }
        }
        return $result;
    }
}