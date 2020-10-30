<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\SVN\Events;

use SystemEventManager;
use Tuleap\SVN\Logs\LastAccessDao;
use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Repository\RepositoryManager;

final class SystemEvent_SVN_IMPORT_CORE_REPOSITORY extends \SystemEvent // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const NAME = 'SystemEvent_SVN_IMPORT_CORE_REPOSITORY';

    private const HOOK_FILES = [
        'post-commit',
        'pre-commit',
        'post-revprop-change',
        'pre-revprop-change',
    ];

    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \BackendSVN
     */
    private $backend_svn;
    /**
     * @var RepositoryManager
     */
    private $repository_manager;
    /**
     * @var LastAccessDao
     */
    private $last_access_dao;

    public static function getDependencies(
        \ProjectManager $project_manager,
        \BackendSVN $backend_svn,
        RepositoryManager $repository_manager,
        LastAccessDao $last_access_dao
    ): array {
        return [
            $project_manager,
            $backend_svn,
            $repository_manager,
            $last_access_dao,
        ];
    }

    public function injectDependencies(
        \ProjectManager $project_manager,
        \BackendSVN $backend_svn,
        RepositoryManager $repository_manager,
        LastAccessDao $last_access_dao
    ): void {
        $this->project_manager = $project_manager;
        $this->backend_svn = $backend_svn;
        $this->repository_manager = $repository_manager;
        $this->last_access_dao = $last_access_dao;
    }

    /**
     * @throws \BackendSVNFileForSimlinkAlreadyExistsException
     * @throws \Tuleap\SVN\Repository\Exception\CannotFindRepositoryException
     */
    public function process(): bool
    {
        $repository = $this->repository_manager->getCoreRepository($this->getProjectFromParameters());

        $repository_hooks_path = realpath($repository->getSystemPath() . '/hooks');
        foreach (self::HOOK_FILES as $file) {
            $hook_file_path = $repository_hooks_path . '/' . $file;
            if (is_file($hook_file_path)) {
                unlink($hook_file_path);
            }
        }

        $this->last_access_dao->importCoreLastCommitDate($repository);

        $status = $this->backend_svn->updateHooks(
            $repository->getProject(),
            $repository->getSystemPath(),
            true,
            dirname(__DIR__, 2) . '/bin',
            basename(__DIR__ . '/../../bin/svn_post_commit.php'),
            dirname(__DIR__, 4) . '/src/utils/php-launcher.sh',
            basename(__DIR__ . '/../../bin/svn_pre_commit.php'),
        );
        if (! $status) {
            $this->error('Hooks cannot be updated');
            return false;
        }

        $this->done();
        return true;
    }

    public function verbalizeParameters($with_link): string
    {
        $project = $this->getProjectFromParameters();
        return $this->verbalizeProjectId($project->getID(), $with_link);
    }

    private function getProjectFromParameters(): \Project
    {
        $project = $this->project_manager->getProject((int) $this->getRequiredParameter(0));
        if (! $project || $project->isError() || ! $project->isActive()) {
            throw new \RuntimeException('Project doesnt exist or is no longer active');
        }
        return $project;
    }

    public static function queueEvent(SystemEventManager $system_event_manager, Repository $repository): void
    {
        $system_event_manager->createEvent(
            self::class,
            $repository->getProject()->getID(),
            self::PRIORITY_HIGH
        );
    }
}
