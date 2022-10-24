<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Group;

use Project;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Gitlab\API\BuildGitlabProjects;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabRequestFault;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\API\GitlabResponseAPIFault;
use Tuleap\Gitlab\Core\ProjectRetriever;
use Tuleap\Gitlab\Group\Token\RetrieveGroupLinksCredentials;
use Tuleap\Gitlab\Permission\GitAdministratorChecker;
use Tuleap\Gitlab\Repository\IntegrateGitlabProject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class GroupLinkSynchronizer
{
    public function __construct(
        private DBTransactionExecutor $db_transaction_executor,
        private RetrieveGroupLink $group_link_retriever,
        private RetrieveGroupLinksCredentials $group_link_credentials_retriever,
        private BuildGitlabProjects $gitlab_projects,
        private UpdateSynchronizationDate $update_synchronization_date,
        private IntegrateGitlabProject $repository_integrator,
        private ProjectRetriever $project_retriever,
        private GitAdministratorChecker $administrator_checker,
    ) {
    }

    /**
     * @return Ok<GroupLinkSynchronized>|Err<Fault>
     */
    public function synchronizeGroupLink(SynchronizeGroupLinkCommand $group_link_command): Ok|Err
    {
        return $this->db_transaction_executor->execute(
            fn() => $this->group_link_retriever->retrieveGroupLink($group_link_command->group_link_id)
                ->andThen(fn(GroupLink $group_link) => $this->project_retriever->retrieveProject($group_link->project_id)
                    ->andThen(fn(Project $project) => $this->administrator_checker->checkUserIsGitAdministrator($project, $group_link_command->user)
                        ->map(static fn() => $project))
                    ->andThen(function (Project $project) use ($group_link) {
                            try {
                                $credentials     = $this->group_link_credentials_retriever->retrieveCredentials($group_link);
                                $gitlab_projects = $this->gitlab_projects->getGroupProjectsFromGitlabAPI($credentials, $group_link->gitlab_group_id);
                            } catch (GitlabRequestException $e) {
                                return Result::err(GitlabRequestFault::fromGitlabRequestException($e));
                            } catch (GitlabResponseAPIException $e) {
                                return Result::err(GitlabResponseAPIFault::fromGitlabResponseAPIException($e));
                            }
                            return Result::ok(new IntegrateRepositoriesInGroupLinkCommand(
                                $group_link,
                                $project,
                                $credentials,
                                $gitlab_projects
                            ));
                    }))
                ->andThen(fn(IntegrateRepositoriesInGroupLinkCommand $command) => $this->integrateProjects($command))
        );
    }

    /**
     * @return Ok<GroupLinkSynchronized>|Err<Fault>
     */
    private function integrateProjects(IntegrateRepositoriesInGroupLinkCommand $command): Ok|Err
    {
        return $this->repository_integrator->integrateSeveralProjects($command)->map(function () use ($command) {
            $this->update_synchronization_date->updateSynchronizationDate($command->group_link, new \DateTimeImmutable());
            return new GroupLinkSynchronized($command->group_link->id, count($command->gitlab_projects));
        });
    }
}
