<?php

use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;
use Sami\Parser\Filter\TrueFilter;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('vendor')
    ->in($dir = 'concrete5/concrete')
;

$versions = GitVersionCollection::create($dir)
    ->addFromTags('8.*')
    ->add('develop', 'develop branch')
;

$sami = new Sami($iterator, array(
    'theme'                => 'concrete5',
    'template_dirs'        => array(__DIR__.'/Resources/themes/concrete5'),
    'versions'             => $versions,
    'title'                => 'concrete5 API',
    'build_dir'            => __DIR__.'/build/%version%',
    'cache_dir'            => __DIR__.'/cache/%version%',
    'remote_repository'    => new GitHubRemoteRepository('concrete5/concrete5', dirname($dir)),
    'default_opened_level' => 2,
));

$sami['filter'] = function () {
    return new TrueFilter();
};

return $sami;
