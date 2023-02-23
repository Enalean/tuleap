<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
use TuleapCfg\Command\ProcessFactory;

final class SiteDeployGitolite3Hooks
{
    private const GITOLITE_BIN      = '/usr/bin/gitolite';
    private const GITOLITE_BASE_DIR = '/var/lib/gitolite';


    public function __construct(
        private readonly ProcessFactory $process_factory,
        private readonly string $gitolite_base_dir = self::GITOLITE_BASE_DIR,
    ) {
    }

    public function deploy(LoggerInterface $logger): void
    {
        if (! $this->hasGitPlugin()) {
            $logger->debug('Git plugin not detected');
            return;
        }

        if (! $this->isGitolite3Setup()) {
            $logger->debug('Gitolite3 is not setup yet');
            return;
        }

        if ($this->createPostReceiveHookSymlink($logger)) {
            $this->deployHooks($logger);
        }
    }

    private function hasGitPlugin(): bool
    {
        return is_dir(__DIR__ . '/../../../../../plugins/git/');
    }

    private function isGitolite3Setup(): bool
    {
        $gitolite_conf_size = @filesize($this->gitolite_base_dir . '/.gitolite/conf/gitolite.conf');
        return $gitolite_conf_size !== false && $gitolite_conf_size > 0;
    }

    private function createPostReceiveHookSymlink(LoggerInterface $logger): bool
    {
        $hook_path = $this->gitolite_base_dir . '/.gitolite/hooks/common/post-receive';
        if (! file_exists($hook_path)) {
            $logger->info('Creating post-receive hook symlink at ' . $hook_path);
            self::symlink(__DIR__ . '/../../../../../plugins/git/hooks/post-receive-gitolite', $hook_path);
            return true;
        }
        return false;
    }

    private function deployHooks(LoggerInterface $logger): void
    {
        $logger->info('Executing gitolite setup --hooks-only');

        $this->process_factory->getProcess(['/usr/bin/sudo', '-u', 'gitolite', self::GITOLITE_BIN, 'setup', '--hooks-only'])->mustRun();
    }

    private static function symlink(string $target_path, string $link_path): void
    {
        $target_realpath = realpath($target_path);
        error_clear_last();
        $symlink_result = symlink($target_realpath, $link_path);
        if ($symlink_result === false) {
            throw new \RuntimeException(sprintf('Cannot create link %s (%s)', $link_path, error_get_last()['message'] ?? 'Unknown error'));
        }
    }
}
