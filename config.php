<?php

use Illuminate\Filesystem\Filesystem;
use Doctum\Parser\Filter\TrueFilter;
use Doctum\RemoteRepository\GitHubRemoteRepository;
use Doctum\Doctum;
use Symfony\Component\Finder\Finder;

// Load dotenv
$env = \Dotenv\Dotenv::createImmutable(__DIR__);
$env->load();
$env->required([
    'BASE_URL',
]);

// Set up our input and output directories
$inputDir = __DIR__ . '/build/composer-staging';
$outputDir = __DIR__ . '/public';

// Set up the composer object, this manages interacting with composer for us
$filesystem = new Filesystem();
$composer = (new \Concrete5\Api\Composer\Composer($filesystem, $inputDir))
    ->prepare();

// Set up a new version collection that gets versions from packagist
$versions = \Concrete5\Api\Version\PackagistVersonCollection::create('concrete5/concrete5', $composer, $filesystem)
    // Only track 5.7.5.*, 8.*
    ->addFromSemver(['>=5.7.5 <8 || >8.2 || >9'])
    // Add the development version too
    ->add('9.2.x-dev');


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
$generator = new Concrete5\Api\Generator\Generator($iterator, [
    // We're just using the default theme for now
    'theme' => 'default',
    'base_url' => $_ENV['BASE_URL'],
    // Provide our packagist version collection
    'versions' => $versions,
    // Set the title
    'title' => 'ConcreteCMS API',
    // Build into the /public directory
    'build_dir' => $outputDir . '/%version%',
    // Use /build for cache
    'cache_dir' => __DIR__ . '/build/%version%',
    // Set the remove repository so that links to github work
    'remote_repository' => new GitHubRemoteRepository('concrete5/concrete5', $inputDir),
    // This is for the left sidebar, how many levels should be expanded by default
    'default_opened_level' => 2,
    // favicon
    'favicon' => $_ENV['BASE_URL'] . 'favicon.ico',
]);


// Add a stub filter to make it easy to change later
$generator['filter'] = function () {
    // This just lets everything get tracked
    return new TrueFilter();
};

// Gotta return the sami instance
return $generator;
