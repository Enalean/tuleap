<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Test\Psalm;

final class PsalmCommandLauncherWithIgnoreDirectory
{
    /**
     * @var string
     */
    private $temporary_directory;
    /**
     * @var PsalmIgnoreDirectory
     */
    private $ignore_directory;
    /**
     * @var ShellPassthrough
     */
    private $shell_passthrough;

    public function __construct(
        string $temporary_directory,
        PsalmIgnoreDirectory $ignore_directory,
        ShellPassthrough $shell_passthrough
    ) {
        $this->temporary_directory = $temporary_directory;
        $this->ignore_directory    = $ignore_directory;
        $this->shell_passthrough   = $shell_passthrough;
    }

    public function execute(string $launch_command, string ...$argv) : int
    {
        $init_script = array_shift($argv);

        if (count($argv) < 2) {
            echo "Usage: $init_script config_path ./src/vendor/bin/psalm-command <psalm-parameters>.\n{config_path} will replaced by the rewritten config.\n";
            return 1;
        }
        $php_interpreter = '';
        if ($launch_command !== $init_script) {
            $php_interpreter = $launch_command . ' ';
        }

        foreach ($argv as $parameter) {
            if (preg_match('/^(--set-baseline|--update-baseline)/', $parameter) === 1) {
                echo "Update or creation of the baseline must be done in a clean source folder. See the target psalm-baseline-update.\n";
                return 1;
            }
        }

        $config_file         = array_shift($argv);
        $config_file_content = @file_get_contents($config_file);
        if ($config_file_content === false) {
            echo "$config_file can not be read\n";
            return 1;
        }
        $config_xml = @simplexml_load_string($config_file_content);
        if ($config_xml === false) {
            echo "$config_file is not a valid XML file\n";
            return 1;
        }

        $command = array_shift($argv);
        if (! $this->isPsalmCommand($command)) {
            echo "$command is not a Psalm command\n";
            return 1;
        }

        $temporary_config_path = $this->writeTemporaryConfigWithExcludedDirectories($config_xml);
        $parameters            = [];
        foreach ($argv as $parameter) {
            $parameters[] = str_replace('{config_path}', escapeshellarg($temporary_config_path), $parameter);
        }

        $exit_code = ($this->shell_passthrough)($php_interpreter . __DIR__ . "/../../../$command " . implode(' ', $parameters));
        @unlink($temporary_config_path);
        return $exit_code;
    }

    private function isPsalmCommand(string $command) : bool
    {
        return preg_match('/(psalm|psalm-language-server|psalm-plugin|psalter|psalm-refactor)$/', $command) === 1;
    }

    private function writeTemporaryConfigWithExcludedDirectories(\SimpleXMLElement $config) : string
    {
        if (! isset($config->projectFiles)) {
            $config->addChild('projectFiles');
        }
        if (! isset($config->projectFiles->ignoreFiles)) {
            $config->projectFiles->addChild('ignoreFiles');
        }

        $excluded_directories = $this->ignore_directory->getIgnoredDirectories();
        foreach ($excluded_directories as $excluded_directory) {
            $config->projectFiles->ignoreFiles->addChild('directory')->addAttribute('name', $excluded_directory);
        }

        return $this->writeTemporaryConfig($config);
    }

    private function writeTemporaryConfig(\SimpleXMLElement $config) : string
    {
        $temp_file = $this->temporary_directory . DIRECTORY_SEPARATOR . 'tuleap_psalm_' . bin2hex(random_bytes(16));
        if (is_file($temp_file)) {
            throw new \RuntimeException("Conflict, temporary config $temp_file already exists");
        }
        touch($temp_file);

        $config->asXML($temp_file);
        return $temp_file;
    }
}
