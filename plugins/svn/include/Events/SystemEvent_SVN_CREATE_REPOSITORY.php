<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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

namespace Tuleap\SVN\Events;

use SystemEvent;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\AccessControl\CannotCreateAccessFileHistoryException;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVNCore\Exception\SVNRepositoryCreationException;
use Tuleap\SVNCore\Exception\SVNRepositoryLayoutInitializationException;
use Tuleap\SVN\Repository;

class SystemEvent_SVN_CREATE_REPOSITORY extends SystemEvent //phpcs:ignore
{
    public const string NAME = 'SystemEvent_SVN_CREATE_REPOSITORY';

    /**
     * @var \Tuleap\SVN\BackendSVN
     */
    private $backend_svn;

    /**
     * @var RepositoryManager
     */
    private $repository_manager;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var AccessFileHistoryCreator
     */
    private $access_file_history_creator;

    public function injectDependencies(
        AccessFileHistoryCreator $access_file_history_creator,
        RepositoryManager $repository_manager,
        \UserManager $user_manager,
        \Tuleap\SVN\BackendSVN $backend_svn,
    ) {
        $this->access_file_history_creator = $access_file_history_creator;
        $this->repository_manager          = $repository_manager;
        $this->user_manager                = $user_manager;
        $this->backend_svn                 = $backend_svn;
    }

    #[\Override]
    public function verbalizeParameters($with_link): string
    {
        return $this->parameters;
    }

    #[\Override]
    public function process(): bool
    {
        try {
            $parameters = $this->getUnserializedParameters();

            $user = $this->user_manager->getUserById($parameters['user_id']);
            if ($user === null) {
                $user = $this->user_manager->getUserAnonymous();
            }

            $repository = $this->repository_manager->getRepositoryById($parameters['repository_id']);

            $this->backend_svn->createRepositorySVN(
                $repository,
                __DIR__ . '/../../bin/',
                $user,
                $parameters['initial_layout'],
            );

            $this->access_file_history_creator->useAVersion($repository, 1);

            $this->done();
            return true;
        } catch (SVNRepositoryLayoutInitializationException | CannotCreateAccessFileHistoryException $exception) {
            $this->warning($exception->getMessage());
            return true;
        } catch (SVNRepositoryCreationException | CannotFindRepositoryException $exception) {
            $this->error($exception->getMessage());
            return false;
        }
    }

    /**
     * @return array{repository_id: int, initial_layout: array, user_id: int}
     * @throws \JsonException
     */
    private function getUnserializedParameters(): array
    {
        return json_decode($this->getParameters(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     */
    public static function serializeParameters(Repository $repository, \PFUser $committer, array $initial_repository_layout): string
    {
        return json_encode([
            'repository_id'  => $repository->getId(),
            'user_id'        => (int) $committer->getId(),
            'initial_layout' => $initial_repository_layout,
        ], JSON_THROW_ON_ERROR);
    }

    #[\Override]
    public function getParametersAsArray()
    {
        try {
            return array_values($this->getUnserializedParameters());
        } catch (\JsonException) {
            return [];
        }
    }
}
