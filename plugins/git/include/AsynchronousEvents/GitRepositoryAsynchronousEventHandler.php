<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

namespace Tuleap\Git\AsynchronousEvents;

use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\MapperBuilder;
use Git_GitoliteDriver;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Git\GitRepositoryDeletionEvent;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\PostInitGitRepositoryWithDataEvent;
use Tuleap\Git\RetrieveGitRepository;
use Tuleap\Queue\WorkerEvent;

final readonly class GitRepositoryAsynchronousEventHandler
{
    public function __construct(
        private MapperBuilder $mapper_builder,
        private DBTransactionExecutor $db_transaction_executor,
        private Git_GitoliteDriver $gitolite_driver,
        private RetrieveGitRepository $repository_retriever,
        private UgroupsToNotifyDao $ugroups_to_notify_dao,
        private UsersToNotifyDao $users_to_notify_dao,
        private EventDispatcherInterface $event_dispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(WorkerEvent $event): void
    {
        $event_name = $event->getEventName();
        if ($event_name === GitRepositoryChangeTask::TOPIC) {
            $mapper = $this->mapper_builder->mapper();

            $change_task = $mapper->map(GitRepositoryChangeTask::class, Source::array($event->getPayload()));

            $this->db_transaction_executor->execute(
                function () use ($change_task): void {
                    $this->repository_retriever
                        ->getExistingRepositoryByIdAndLockItForChange($change_task->repository_id)
                        ->apply($this->handleRepositoryChange(...));
                }
            );
        } elseif ($event_name === GitRepositoryForkTask::TOPIC) {
            $mapper = $this->mapper_builder->mapper();

            $fork_task = $mapper->map(GitRepositoryForkTask::class, Source::array($event->getPayload()));

            $this->db_transaction_executor->execute(
                function () use ($fork_task): void {
                    $this->repository_retriever
                        ->getExistingRepositoryByIdAndLockItForChange($fork_task->repository_id)
                        ->apply($this->handleRepositoryFork(...));
                }
            );
        }
    }

    private function handleRepositoryChange(\GitRepository $repository): void
    {
        $this->logger->debug('Requesting configuration update for Git repo #' . $repository->getId());
        $this->gitolite_driver->dumpProjectRepoConf($repository->getProject());

        if ($repository->getDeletionDate() !== \GitDao::NOT_DELETED_DATE) {
            $this->handleRepositoryDeletion($repository);
        }
    }

    private function handleRepositoryDeletion(\GitRepository $repository): void
    {
        $this->logger->debug('Deleting Git repo #' . $repository->getId());
        $this->users_to_notify_dao->deleteByRepositoryId($repository->getId());
        $this->ugroups_to_notify_dao->deleteByRepositoryId($repository->getId());
        $this->event_dispatcher->dispatch(new GitRepositoryDeletionEvent($repository));
        $repository->delete();
    }

    private function handleRepositoryFork(\GitRepository $new_repository): void
    {
        $repository_id = $new_repository->getId();
        $this->logger->debug("Creating Git repo #$repository_id from a fork");
        $this->handleRepositoryChange($new_repository);
        $this->repository_retriever
            ->getExistingRepositoryByIdAndLockItForChange($new_repository->getParentId())
            ->apply(
                function (\GitRepository $source_repository) use ($new_repository): void {
                    $new_repository->getBackend()
                        ->forkOnFilesystem($source_repository, $new_repository);
                    $this->event_dispatcher->dispatch(new PostInitGitRepositoryWithDataEvent($new_repository));
                }
            );
    }
}
