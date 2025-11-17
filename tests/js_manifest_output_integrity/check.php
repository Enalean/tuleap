#! /usr/bin/env php
<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../src/vendor/autoload.php';

// Ensure entrypoints listed in JS manifest files are present on disk

$logger = new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(\Symfony\Component\Console\Output\ConsoleOutput::VERBOSITY_VERY_VERBOSE));

$path = \Psl\Env\current_dir();

$frontend_assets_path = $path . '/frontend-assets';
if (! \Psl\Filesystem\is_directory($frontend_assets_path)) {
    $logger->debug('Skipping ' . $path . ', no frontend assets to evaluate');
    exit(0);
}

/**
 * @param array<string, array{"file": string}> $manifest
 */
function checkConsistencyViteAssets(\Psr\Log\LoggerInterface $logger, string $path_assets, array $manifest): never
{
    foreach ($manifest as $name => $asset_info) {
        $file_path = $asset_info['file'];
        if (! \Psl\Filesystem\is_file($path_assets . '/' . $file_path)) {
            $logger->error(sprintf('File %s (%s) not found in %s', $name, $file_path, $path_assets));
            exit(1);
        }
    }
    exit(0);
}

/**
 * @param array<string, string> $manifest
 */
function checkConsistencyWebpackAssets(\Psr\Log\LoggerInterface $logger, string $path_assets, array $manifest): never
{
    foreach ($manifest as $name => $file_path) {
        if (! \Psl\Filesystem\is_file($path_assets . '/' . $file_path)) {
            $logger->error(sprintf('File %s (%s) not found in %s', $name, $file_path, $path_assets));
            exit(1);
        }
    }
    exit(0);
}

$logger->info('Checking ' . $path);

$vite_manifest_path = $frontend_assets_path . '/.vite/manifest.json';
if (\Psl\Filesystem\is_file($vite_manifest_path)) {
    checkConsistencyViteAssets($logger, $frontend_assets_path, \Psl\Json\decode(\Psl\File\read($vite_manifest_path)));
}

$webpack_manifest_path = $frontend_assets_path . '/manifest.json';
if (\Psl\Filesystem\is_file($webpack_manifest_path)) {
    checkConsistencyWebpackAssets($logger, $frontend_assets_path, \Psl\Json\decode(\Psl\File\read($webpack_manifest_path)));
}

$logger->error('No manifest found for ' . $path);
exit(1);
