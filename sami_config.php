<?php

use Illuminate\Filesystem\Filesystem;
use Sami\Parser\Filter\TrueFilter;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Sami;
use Symfony\Component\Finder\Finder;

// Set up our input and output directories
$inputDir = __DIR__ . '/build/composer-staging';
$outputDir = __DIR__ . '/public';

// Set up the composer object, this manages interacting with composer for us
$filesystem = new Filesystem();
$composer = (new \Concrete5\Api\Composer\Composer($filesystem, $inputDir))
    ->prepare();

// Set up a new version collection that gets versions from packagist
$versions = \Concrete5\Api\Version\PackagistVersonCollection::create('concrete5/concrete5', $composer, $filesystem)
    // Only track 5.7.5.* and 8.*
    ->addFromComposer('{5.7.5.*,8.*}')
    // Add the development version too
    ->add('dev-develop', '8.x-dev')
;

// Set up the file finder (It can be any iterator)
$iterator = Finder::create()
    ->files()
    ->name('*.php')
    // Exclude things we don't want to scan
    ->exclude('vendor')
    ->exclude('tests')
    ->exclude('build')
    ->exclude('concrete/vendor')
    ->exclude('web/concrete/vendor')
    // Look in `/concrete` and `/web/concrete` (for 5.7)
    ->in($inputDir . '/{concrete,web/concrete}');

// Prepare the sami object
$sami = new Sami($iterator, [
    // We're just using the default theme for now
    'theme' => 'default',

    // Provide our packagist version collection
    'versions' => $versions,
    // Set the title
    'title' => 'concrete5 v8+ API',
    // Build into the /public directory
    'build_dir' => $outputDir . '/%version%',
    // Use /build for cache
    'cache_dir' => __DIR__ . '/build/%version%',
    // Set the remove repository so that links to github work
    'remote_repository' => new GitHubRemoteRepository('concrete5/concrete5', $inputDir),
    // This is for the left sidebar, how many levels should be expanded by default
    'default_opened_level' => 2,
]);


// Add a stub filter to make it easy to change later
$sami['filter'] = function () {
    // This just lets everything get tracked
    return new TrueFilter();
};

// Gotta return the sami instance
return $sami;
