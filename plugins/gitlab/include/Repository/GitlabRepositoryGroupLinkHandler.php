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

namespace Tuleap\Gitlab\Repository;

use Project;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Git\Branch\BranchName;
use Tuleap\Git\Branch\InvalidBranchNameException;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\Group\GroupAlreadyLinkedToProjectException;
use Tuleap\Gitlab\Group\GroupLinkFactory;
use Tuleap\Gitlab\Group\IntegrateRepositoriesInGroupLinkCommand;
use Tuleap\Gitlab\Group\NewGroupLink;
use Tuleap\Gitlab\Group\ProjectAlreadyLinkedToGroupException;
use Tuleap\Gitlab\Group\Token\InsertGroupLinkToken;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

final class GitlabRepositoryGroupLinkHandler
{
    private const string FAKE_BRANCH_NAME = 'branch_name';

    public function __construct(
        private DBTransactionExecutor $db_transaction_executor,
        private GroupLinkFactory $gitlab_group_factory,
        private InsertGroupLinkToken $group_token_inserter,
        private IntegrateGitlabProject $gitlab_project_integrator,
    ) {
    }

    /**
     * @param GitlabProject[] $gitlab_projects
     *
     * @return Ok<GitlabGroupRepresentation>|Err<Fault>
     *
     * @throws InvalidBranchNameException
     * @throws GroupAlreadyLinkedToProjectException
     * @throws ProjectAlreadyLinkedToGroupException
     */
    public function integrateGitlabRepositoriesInProject(
        Credentials $credentials,
        array $gitlab_projects,
        Project $project,
        NewGroupLink $new_group,
    ): Ok|Err {
        BranchName::fromBranchNameShortHand($new_group->prefix_branch_name . self::FAKE_BRANCH_NAME);

        return $this->db_transaction_executor->execute(
            function () use ($credentials, $gitlab_projects, $project, $new_group) {
                $gitlab_group = $this->gitlab_group_factory->createGroup($new_group);

                $this->group_token_inserter->insertToken($gitlab_group, $credentials->getApiToken()->getToken());

                return $this->gitlab_project_integrator->integrateSeveralProjects(
                    new IntegrateRepositoriesInGroupLinkCommand(
                        $gitlab_group,
                        $project,
                        $credentials,
                        $gitlab_projects
                    )
                )->map(fn() => new GitlabGroupRepresentation($gitlab_group->id, count($gitlab_projects)));
            }
        );
    }
}
