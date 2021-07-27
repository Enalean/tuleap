<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Preload;

use DirectoryIterator;
use SplFileInfo;

final class PreloadGenerator
{
    public function dumpFromComposerJson(string $composer_json_path): void
    {
        if (! \is_file($composer_json_path)) {
            throw new \RuntimeException("$composer_json_path is not a valid file path");
        }
        try {
            $base_dir          = dirname($composer_json_path);
            $preload_file_path = $base_dir . '/vendor/preload.php';
            if (\is_file($preload_file_path)) {
                \unlink($preload_file_path);
            }
            $composer_json = \json_decode(\file_get_contents($composer_json_path), true, 512, JSON_THROW_ON_ERROR);
            if (isset($composer_json['tuleap']['preload'])) {
                $preload_content = $this->load(
                    $base_dir,
                    $composer_json['tuleap']['preload']['include'] ?? [],
                    $composer_json['tuleap']['preload']['exclude'] ?? [],
                );

                $this->save($preload_file_path, $preload_content);
            }
        } catch (\JsonException $exception) {
            throw new \RuntimeException("$composer_json_path is not a valid json file");
        }
    }

    private function load(string $basedir, array $include_dirs = [], array $ignored_paths = []): string
    {
        $base = realpath($basedir);
        if ($base === false) {
            throw new \RuntimeException('Invalid directory ' . $basedir);
        }
        $class_map      = require $basedir . '/vendor/composer/autoload_classmap.php';
        $file_class_map = array_flip($class_map);
        if (! $include_dirs) {
            $include_dirs = [''];
        }
        $ignored_paths[] = '/vendor/composer';
        $preload_content = '';
        foreach ($include_dirs as $include_dir) {
            $preload_content .= $this->recursiveLoad($base, new SplFileInfo($base . $include_dir), $ignored_paths, $file_class_map);
        }

        return $preload_content;
    }

    private function recursiveLoad(string $base_dir, SplFileInfo $path, array $ignored_paths, array $file_class_map): string
    {
        $preload_content = '';
        foreach ($ignored_paths as $ignored_dir) {
            if (strpos($path->getPathname(), $base_dir . $ignored_dir) === 0) {
                return '';
            }
        }
        if ($path->isFile() && isset($file_class_map[$path->getPathname()])) {
            $relative_path    = \substr($path->getPathname(), \strlen($base_dir));
            $preload_content .= sprintf('\opcache_compile_file($_root_directory . \'%s\');%s', $relative_path, PHP_EOL);
        }
        if ($path->isDir()) {
            foreach (new DirectoryIterator($path->getPathname()) as $file) {
                \assert($file instanceof SplFileInfo);
                if ($file->isDot()) {
                    continue;
                }
                $preload_content .= $this->recursiveLoad($base_dir, $file, $ignored_paths, $file_class_map);
            }
        }
        return $preload_content;
    }

    private function save(string $file_path, string $preload_content): void
    {
        if (trim($preload_content) === '') {
            return;
        }

        file_put_contents(
            $file_path,
            <<<EOT
            <?php
            \$_root_directory = \dirname(__DIR__);

            $preload_content
            EOT
        );
    }
}
