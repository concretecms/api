<?php


namespace Concrete5\Api\Generator;


use Doctum\Project;

class Renderer extends \Doctum\Renderer\Renderer
{

    protected function renderStaticTemplates(Project $project, $callback = null)
    {
        $cache = $project->getCacheDir();
        $dir = $project->getBuildDir();

        $lock = $cache . '/../MAX_VERSION';
        $index = $dir . '/../index.html';

        $version = $project->getVersion();
        $highestVersion = 0;

        if (file_exists($lock)) {
            $highestVersion = file_get_contents($lock);
            if (version_compare($highestVersion, $project->getVersion(), '>')) {
                $version = $highestVersion;
            }
        }

        if (!file_exists($index) || $version !== $highestVersion) {
            file_put_contents($lock, $version);
            file_put_contents(
                $index,
                <<<HTML
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta http-equiv="refresh" content="0;url={$_ENV['BASE_URL']}{$version}/" />
    </head>
    <body>
    </body>
</html>
HTML
            );
        }

        parent::renderStaticTemplates($project, $callback);
    }

}
