#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * Builds the Symfony Flex endpoint served from flex/ on the main branch.
 *
 * Flex never reads recipes from inside an installed package: it only fetches
 * them from HTTP endpoints listed in extra.symfony.endpoint. This script turns
 * the recipe/ sources into the JSON files such an endpoint must expose.
 *
 * Usage:
 *   php bin/build-flex-endpoint.php           write flex/
 *   php bin/build-flex-endpoint.php --check   fail if flex/ is out of date
 */

const PACKAGE = 'lgarret/health-check-bundle';
const REPOSITORY = 'github.com/LouisGarret/health-check-bundle';
const BRANCH = 'main';
const BASE_URL = 'https://raw.githubusercontent.com/LouisGarret/health-check-bundle/main/flex';

$root = \dirname(__DIR__);
$recipeDir = $root.'/recipe/'.PACKAGE;
$outputDir = $root.'/flex';
$check = \in_array('--check', $argv, true);

if (!is_dir($recipeDir)) {
    fwrite(\STDERR, "No recipe found in $recipeDir\n");
    exit(1);
}

$versions = array_values(array_filter(scandir($recipeDir), static fn (string $e): bool => '.' !== $e[0]));
sort($versions, \SORT_NATURAL);

$files = [];

foreach ($versions as $version) {
    $dir = $recipeDir.'/'.$version;
    $manifest = json_decode((string) file_get_contents($dir.'/manifest.json'), true, 512, \JSON_THROW_ON_ERROR);

    $recipeFiles = [];
    /** @var \SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $relative = substr($file->getPathname(), \strlen($dir) + 1);

        if ('manifest.json' === $relative) {
            continue;
        }

        // Flex joins "contents" back with "\n", so splitting on it round-trips exactly.
        $recipeFiles[$relative] = [
            'contents' => explode("\n", (string) file_get_contents($file->getPathname())),
            'executable' => $file->isExecutable(),
        ];
    }

    ksort($recipeFiles);

    $data = ['manifest' => $manifest, 'files' => $recipeFiles];
    // The ref lets Flex detect that a recipe changed and offer an update.
    $data['ref'] = sha1(json_encode($data, \JSON_THROW_ON_ERROR));

    $files[str_replace('/', '.', PACKAGE).'.'.$version.'.json'] = ['manifests' => [PACKAGE => $data]];
}

$files['index.json'] = [
    'recipes' => [PACKAGE => $versions],
    'branch' => BRANCH,
    'is_contrib' => false,
    '_links' => [
        'repository' => REPOSITORY,
        'origin_template' => '{package}:{version}@'.REPOSITORY.':'.BRANCH,
        'recipe_template' => BASE_URL.'/{package_dotted}.{version}.json',
        'recipe_template_relative' => '{package_dotted}.{version}.json',
    ],
];

if (!is_dir($outputDir) && !$check) {
    mkdir($outputDir, 0o777, true);
}

$stale = [];

foreach ($files as $name => $content) {
    $json = json_encode($content, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR)."\n";
    $path = $outputDir.'/'.$name;

    if ($check) {
        if (!is_file($path) || file_get_contents($path) !== $json) {
            $stale[] = 'flex/'.$name;
        }

        continue;
    }

    file_put_contents($path, $json);
    echo "wrote flex/$name\n";
}

if ($check && $stale) {
    fwrite(\STDERR, "Flex endpoint out of date: ".implode(', ', $stale)."\nRun: php bin/build-flex-endpoint.php\n");
    exit(1);
}

if ($check) {
    echo "Flex endpoint is up to date.\n";
}
