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

namespace TuleapCfg\Command\SiteDeploy\Gitolite3;

use Psr\Log\LoggerInterface;
use Tuleap\File\FileWriter;

final class SiteDeployGitolite3
{
    private const GITOLITE_BASE_DIR                    = '/var/lib/gitolite';
    private const GITOLITE_RC_CONFIG                   = '/var/lib/gitolite/.gitolite.rc';
    private const GITOLITE_PROFILE                     = '/var/lib/gitolite/.profile';
    private const MARKER_ONLY_PRESENT_GITOLITE3_CONFIG = '%RC =';

    public function deploy(LoggerInterface $logger): void
    {
        if (! $this->hasGitPlugin()) {
            $logger->debug('Git plugin not detected');
            return;
        }

        if (! self::hasTuleapGitBin()) {
            $logger->error("No Tuleap git detected whereas git plugin in installed, cannot proceed");
            return;
        }

        $this->updateGitoliteShellProfile($logger);

        $this->updateGitoliteConfig($logger);
    }

    private function updateGitoliteShellProfile(LoggerInterface $logger): void
    {
        if (! file_exists(self::GITOLITE_BASE_DIR)) {
            $logger->warning('No ' . self::GITOLITE_BASE_DIR . '. Skipping update of ' . self::GITOLITE_PROFILE);
            return;
        }

        $expected_gitolite_profile = $this->getExpectedGitoliteProfileContent();
        $current_gitolite_profile  = '';
        if (is_file(self::GITOLITE_PROFILE)) {
            $current_gitolite_profile = file_get_contents(self::GITOLITE_PROFILE);
        }

        if ($expected_gitolite_profile !== $current_gitolite_profile) {
            $logger->info('Updating ' . self::GITOLITE_PROFILE);
            $this->writeFile(self::GITOLITE_PROFILE, $expected_gitolite_profile);
        }

        if (file_exists(self::GITOLITE_BASE_DIR . '/.bash_profile')) {
            unlink(self::GITOLITE_BASE_DIR . '/.bash_profile');
        }
    }

    private function updateGitoliteConfig(LoggerInterface $logger): void
    {
        if (! $this->hasAGitolite3Config()) {
            $logger->debug('Gitolite3 not detected');
            return;
        }

        $expected_gitolite_config = $this->getExpectedGitolite3ConfigContent();
        $current_gitolite_config  = file_get_contents(self::GITOLITE_RC_CONFIG);

        if ($expected_gitolite_config !== $current_gitolite_config) {
            $logger->info('Updating ' . self::GITOLITE_RC_CONFIG);
            $this->writeFile(self::GITOLITE_RC_CONFIG, $expected_gitolite_config);
        }
    }

    private function hasAGitolite3Config(): bool
    {
        return is_file(self::GITOLITE_RC_CONFIG) &&
               strpos(file_get_contents(self::GITOLITE_RC_CONFIG), self::MARKER_ONLY_PRESENT_GITOLITE3_CONFIG) !== false;
    }

    private function hasGitPlugin(): bool
    {
        return is_dir(__DIR__ . '/../../../../../plugins/git/');
    }

    private static function hasTuleapGitBin(): bool
    {
        return is_dir('/usr/lib/tuleap/git');
    }

    private function getExpectedGitolite3ConfigContent(): string
    {
        $config = file_get_contents(__DIR__ . '/../../../../../plugins/git/etc/gitolite3.rc.dist');

        $config = str_replace(
            ['# GROUPLIST_PGM', "'ssh-authkeys',"],
            ['GROUPLIST_PGM', "# 'ssh-authkeys',"],
            $config
        );


        $git_bin_path = '/usr/lib/tuleap/git/bin';

        $config = preg_replace(
            '/^\$ENV{PATH} =.*$/m',
            '$ENV{PATH} = "' . $git_bin_path . ':$ENV{PATH}";',
            $config
        );

        return $config;
    }

    private function getExpectedGitoliteProfileContent(): string
    {
        return 'export PATH=/usr/lib/tuleap/git/bin${PATH:+:${PATH}}' . "\n";
    }

    /**
     * @psalm-param non-empty-string $path
     */
    private function writeFile(string $path, string $content): void
    {
        FileWriter::writeFile($path, $content);
        if (chown($path, 'gitolite') === false) {
            throw new \RuntimeException('Unable to set the owner to gitolite on ' . $path);
        }

        if (chgrp($path, 'gitolite') === false) {
            throw new \RuntimeException('Unable to set the group owner to gitolite on ' . $path);
        }
    }
}
