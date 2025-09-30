<?php
/**
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
  *
  * This file is a part of Codendi.
  *
  * Codendi is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Codendi is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Codendi. If not, see <http://www.gnu.org/licenses/
  */

use Tuleap\Git\GitRepositoryDeletionEvent;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;

/**
 * Description of SystemEvent_GIT_REPO_DELETE
 */
class SystemEvent_GIT_REPO_DELETE extends SystemEvent //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public const string NAME = 'GIT_REPO_DELETE';

    /** @var EventManager */
    private $event_manager;

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var UgroupsToNotifyDao */
    private $ugroups_to_notify_dao;

    /** @var UsersToNotifyDao */
    private $users_to_notify_dao;

    public function injectDependencies(
        GitRepositoryFactory $repository_factory,
        \Psr\Log\LoggerInterface $logger,
        UgroupsToNotifyDao $ugroups_to_notify_dao,
        UsersToNotifyDao $users_to_notify_dao,
        EventManager $event_manager,
    ) {
        $this->repository_factory    = $repository_factory;
        $this->logger                = $logger;
        $this->ugroups_to_notify_dao = $ugroups_to_notify_dao;
        $this->users_to_notify_dao   = $users_to_notify_dao;
        $this->event_manager         = $event_manager;
    }

    #[\Override]
    public function process()
    {
        $parameters = $this->getParametersAsArray();
        //project id
        $projectId = 0;
        if (! empty($parameters[0])) {
            $projectId = (int) $parameters[0];
        } else {
            $this->error('Missing argument project id');
            return false;
        }
        //repo id
        $repositoryId = 0;
        if (! empty($parameters[1])) {
            $repositoryId = (int) $parameters[1];
        } else {
            $this->error('Missing argument repository id');
            return false;
        }

        $repository = $this->repository_factory->getDeletedRepository($repositoryId);
        if ($repository === null) {
            $this->error('Cannot find deleted repository #' . $repositoryId);
            return false;
        }
        if ($repository->getProjectId() != $projectId) {
            $this->error('Bad project id');
            return false;
        }

        return $this->deleteRepo($repository);
    }

    private function deleteRepo(GitRepository $repository): bool
    {
        $path = $repository->getPath();

        try {
            $this->logger->debug('Deleting repository ' . $path);
            $this->users_to_notify_dao->deleteByRepositoryId($repository->getId());
            $this->ugroups_to_notify_dao->deleteByRepositoryId($repository->getId());
            $this->event_manager->processEvent(new GitRepositoryDeletionEvent($repository));
            $repository->delete();
        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->logger->error('Error while deleting repository ' . $path, ['exception' => $e]);
            return false;
        }
        $this->done();
        return true;
    }

    #[\Override]
    public function verbalizeParameters($with_link)
    {
        return $this->parameters;
    }
}
