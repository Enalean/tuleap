<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Git\DefaultBranch\CannotExecuteDefaultBranchUpdateException;
use Tuleap\Git\DefaultBranch\DefaultBranchUpdateExecutor;

class SystemEvent_GIT_REPO_UPDATE extends SystemEvent
{
    public const NAME = 'GIT_REPO_UPDATE';

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var Git_SystemEventManager */
    private $system_event_manager;

    private DefaultBranchUpdateExecutor $default_branch_update_executor;

    public function injectDependencies(
        GitRepositoryFactory $repository_factory,
        Git_SystemEventManager $system_event_manager,
        DefaultBranchUpdateExecutor $default_branch_update_executor,
    ) {
        $this->repository_factory             = $repository_factory;
        $this->system_event_manager           = $system_event_manager;
        $this->default_branch_update_executor = $default_branch_update_executor;
    }

    public static function queueInSystemEventManager(SystemEventManager $system_event_manager, GitRepository $repository)
    {
        $system_event_manager->createEvent(
            self::NAME,
            $repository->getId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    private function getRepositoryIdFromParameters(): int
    {
        $parameters = $this->getParametersAsArray();
        return (int) $parameters[0];
    }

    private function getRepositoryFromParameters()
    {
        return $this->repository_factory->getRepositoryById($this->getRepositoryIdFromParameters());
    }

    private function getDefaultBranchIfItExistsFromParameters(): ?string
    {
        $parameters = $this->getParametersAsArray();
        return $parameters[1] ?? null;
    }

    public function process()
    {
        $repository = $this->getRepositoryFromParameters();
        if (! $repository) {
            if ($this->repository_factory->getDeletedRepository($this->getRepositoryIdFromParameters())) {
                $this->done('Unable to update a repository marked as deleted');
                return;
            }

            $this->warning('Unable to find repository, perhaps it was deleted in the mean time?');
            return;
        }

        $backend = $repository->getBackend();

        if (! $backend->updateRepoConf($repository)) {
            $this->error('Unable to update gitolite configuration for repository with ID ' . $this->getRepositoryIdFromParameters());
            return;
        }

        $this->system_event_manager->queueGrokMirrorManifest($repository);

        $default_branch = $this->getDefaultBranchIfItExistsFromParameters();
        if ($default_branch !== null) {
            $driver = $backend->getDriver();
            $driver->commit(sprintf('Modifications from event #%d (repository #%d, default branch:%s)', $this->getId(), $repository->getId(), $default_branch));
            $driver->push();

            try {
                $this->default_branch_update_executor->setDefaultBranch(Git_Exec::buildFromRepository($repository), $default_branch);
            } catch (CannotExecuteDefaultBranchUpdateException $exception) {
                $this->error($exception->getMessage());
                return;
            }
        }

        $this->done();
    }

    public function verbalizeParameters($with_link)
    {
        $html_purifier  = Codendi_HTMLPurifier::instance();
        $default_branch = $this->getDefaultBranchIfItExistsFromParameters();

        if ($with_link) {
            $repository = $this->getRepositoryFromParameters();
            if ($repository) {
                $link_name = $repository->getName();
                if ($default_branch !== null) {
                    $link_name .= ' (' . $default_branch . ')';
                }

                return '<a href="/plugins/git/?action=repo_management&group_id=' . urlencode($repository->getProjectId()) . '&repo_id=' . urlencode($repository->getId()) . '">' . $html_purifier->purify($link_name) . '</a>';
            }
        }

        $verbalized_parameters = $this->getRepositoryIdFromParameters();
        if ($default_branch !== null) {
            $verbalized_parameters .= ' (' . $default_branch . ')';
        }
        return $html_purifier->purify($verbalized_parameters);
    }
}
