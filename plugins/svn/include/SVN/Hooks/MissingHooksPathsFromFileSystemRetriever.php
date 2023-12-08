<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\SVN\Hooks;

use ForgeConfig;
use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\RepositoryManager;

final class MissingHooksPathsFromFileSystemRetriever implements MissingHooksPathsRetriever
{
    private int $application_owner_uid;

    public function __construct(private readonly LoggerInterface $svn_logger, private readonly RepositoryManager $repository_manager)
    {
        $pwnam                       = posix_getpwnam(ForgeConfig::getApplicationUserLogin());
        $this->application_owner_uid = $pwnam['uid'];
    }

    /**
     * @return Ok<list<Repository>>|Err<Fault>
     */
    public function retrieveAllMissingHooksPaths(): Ok|Err
    {
        $repositories = [];
        foreach ($this->repository_manager->getAllRepositoriesInActiveProjects() as $repository) {
            $repository_path = $repository->getSystemPath();
            if (! is_dir($repository_path)) {
                $this->svn_logger->info('No ' . $repository_path . ' skipping, in creation ?');
                continue;
            }
            $hooks_path = $repository_path . '/hooks';
            if (! is_dir($hooks_path)) {
                return Result::err(Fault::fromMessage('SVN repository ' . $repository_path . 'do not have `hooks` directory'));
            }

            if (! $this->isHookFileValid($hooks_path . '/' . \BackendSVN::PRE_COMMIT_HOOK)) {
                $repositories[] = $repository;
                continue;
            }

            if (! $this->isHookFileValid($hooks_path . '/' . \BackendSVN::POST_COMMIT_HOOK)) {
                $repositories[] = $repository;
                continue;
            }

            if (! $this->isHookLinkValid($hooks_path . '/' . \BackendSVN::PRE_REVPROP_CHANGE_HOOK)) {
                $repositories[] = $repository;
                continue;
            }

            if (! $this->isHookLinkValid($hooks_path . '/' . \BackendSVN::POST_REVPROP_CHANGE_HOOK)) {
                $repositories[] = $repository;
            }
        }

        return Result::ok($repositories);
    }

    private function isHookFileValid(string $hook_file_path): bool
    {
        return is_file($hook_file_path) && is_executable($hook_file_path) && $this->isOwnedByApplicationUser($hook_file_path);
    }

    private function isOwnedByApplicationUser(string $hook_file_path): bool
    {
        return stat($hook_file_path)['uid'] === $this->application_owner_uid;
    }

    private function isHookLinkValid(string $hook_file_path): bool
    {
        return is_link($hook_file_path);
    }
}
