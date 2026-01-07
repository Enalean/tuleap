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
use TuleapCfg\Command\ProcessFactory;
use TuleapCfg\Command\SystemControlCommand;
use TuleapCfg\Command\SystemControlSystemd;

final readonly class SiteDeployGitolite3
{
    private const string GITOLITE_BASE_DIR       = '/var/lib/gitolite';
    private const string GITOLITE_RC_CONFIG      = '/var/lib/gitolite/.gitolite.rc';
    private const string GITOLITE_PROFILE        = '/var/lib/gitolite/.profile';
    private const string SSHD_TULEAP_CONFIG_PATH = '/etc/ssh/sshd_config.d/10-tuleap.conf';

    public function __construct(private ProcessFactory $process_factory)
    {
    }

    public function deploy(LoggerInterface $logger): void
    {
        if (! $this->hasGitPlugin()) {
            $logger->debug('Git plugin not detected');
            return;
        }

        if (! self::hasTuleapGitBin()) {
            $logger->error('No Tuleap git detected whereas git plugin in installed, cannot proceed');
            return;
        }

        $this->updateGitoliteShellProfile($logger);
        $this->setupGitoliteDirectoryStructure();
        $this->updateGitoliteConfig($logger);
        $this->deployTuleapSSHDConfig($logger);
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
        $expected_gitolite_config = $this->getExpectedGitolite3ConfigContent();
        $current_gitolite_config  = '';
        if (\Psl\Filesystem\is_file(self::GITOLITE_RC_CONFIG)) {
            $current_gitolite_config = \Psl\File\read(self::GITOLITE_RC_CONFIG);
        }

        if ($expected_gitolite_config !== $current_gitolite_config) {
            $logger->info('Updating ' . self::GITOLITE_RC_CONFIG);
            $this->writeFile(self::GITOLITE_RC_CONFIG, $expected_gitolite_config);
        }
    }

    private function setupGitoliteDirectoryStructure(): void
    {
        $repositories_symlink = self::GITOLITE_BASE_DIR . '/repositories';
        if (! \Psl\Filesystem\is_symbolic_link($repositories_symlink)) {
            \Psl\Filesystem\create_symbolic_link('/var/lib/tuleap/gitolite/repositories', $repositories_symlink);
        }

        $dot_gitolite_dir = self::GITOLITE_BASE_DIR . '/.gitolite';
        $this->createOrUpdateGitoliteDirectory($dot_gitolite_dir, 0750);
        $this->createOrUpdateGitoliteDirectory($dot_gitolite_dir . '/conf', 0770);
        $this->createOrUpdateGitoliteDirectory($dot_gitolite_dir . '/conf/projects', 0770);
        $this->createOrUpdateGitoliteDirectory($dot_gitolite_dir . '/hooks', 0750);
        $this->createOrUpdateGitoliteDirectory($dot_gitolite_dir . '/hooks/common', 0750);
        $this->changeHooksPermissions($dot_gitolite_dir . '/hooks/common');
        $this->createOrUpdateGitoliteDirectory($dot_gitolite_dir . '/logs', 0750);
        $this->changeLogsPermissions($dot_gitolite_dir . '/logs');
    }

    /**
     * @param non-empty-string $path
     */
    private function createOrUpdateGitoliteDirectory(string $path, int $permissions): void
    {
        if (\Psl\Filesystem\is_directory($path)) {
            \Psl\Filesystem\change_permissions($path, $permissions);
        } else {
            \Psl\Filesystem\create_directory($path, $permissions);
        }
        $this->setGitoliteOwnershipOnPath($path);
    }

    /**
     * @psalm-param non-empty-string $path
     */
    private function changeLogsPermissions(string $path): void
    {
        $files = \Psl\Filesystem\read_directory($path);
        foreach ($files as $file) {
            if (\Psl\Filesystem\is_symbolic_link($file)) {
                continue;
            }
            if (\Psl\Filesystem\is_file($file)) {
                \Psl\Filesystem\change_permissions($file, 0640);
            }
        }
    }

    /**
     * @psalm-param non-empty-string $path
     */
    private function changeHooksPermissions(string $path): void
    {
        $files = \Psl\Filesystem\read_directory($path);
        foreach ($files as $file) {
            if (\Psl\Filesystem\is_symbolic_link($file)) {
                continue;
            }
            if (\Psl\Filesystem\is_file($file)) {
                \Psl\Filesystem\change_permissions($file, 0755);
            }
        }
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

    private function deployTuleapSSHDConfig(LoggerInterface $logger): void
    {
        if (getenv(SystemControlCommand::ENV_SYSTEMCTL) === SystemControlCommand::ENV_SYSTEMCTL_DOCKER) {
            $logger->debug('Container environment, skipping deployment of SSHD config');
            return;
        }

        $expected_sshd_tuleap_config = \Psl\File\read(__DIR__ . '/../../../../../plugins/git/etc/tuleap-sshd.config');
        $current_sshd_tuleap_config  = '';
        if (\Psl\Filesystem\is_file(self::SSHD_TULEAP_CONFIG_PATH)) {
            $current_sshd_tuleap_config = \Psl\File\read(self::SSHD_TULEAP_CONFIG_PATH);
        }

        if ($expected_sshd_tuleap_config === $current_sshd_tuleap_config) {
            $logger->debug(self::SSHD_TULEAP_CONFIG_PATH . ' is up to date, nothing to do');
            return;
        }

        FileWriter::writeFile(self::SSHD_TULEAP_CONFIG_PATH, $expected_sshd_tuleap_config, 0600);

        $sshd_test_status = $this->process_factory->getProcess(['/usr/sbin/sshd', '-t'])->run();
        if ($sshd_test_status !== 0) {
            $logger->warning(sprintf('SSHd test failed with exit code %d, removing %s. Please check your SSHd configuration.', $sshd_test_status, self::SSHD_TULEAP_CONFIG_PATH));
            \Psl\Filesystem\delete_file(self::SSHD_TULEAP_CONFIG_PATH);
            return;
        }

        $logger->info('Reloading sshd.service');
        $sshd_system_control = new SystemControlSystemd($this->process_factory, false, 'reload', 'sshd.service');
        $sshd_system_control->run();
        if (! $sshd_system_control->isSuccessful()) {
            throw new \RuntimeException('SSHd reload failed, check your sshd.service');
        }
    }

    /**
     * @psalm-param non-empty-string $path
     */
    private function writeFile(string $path, string $content): void
    {
        FileWriter::writeFile($path, $content);
        $this->setGitoliteOwnershipOnPath($path);
    }

    /**
     * @param non-empty-string $path
     */
    private function setGitoliteOwnershipOnPath(string $path): void
    {
        if (chown($path, 'gitolite') === false) {
            throw new \RuntimeException('Unable to set the owner to gitolite on ' . $path);
        }

        if (chgrp($path, 'gitolite') === false) {
            throw new \RuntimeException('Unable to set the group owner to gitolite on ' . $path);
        }
    }
}
