<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git\ForkRepositories\DoFork;

use GitRepositoryManager;
use PFUser;
use Project;
use ProjectHistoryDao;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Git\Permissions\VerifyUserIsGitAdministrator;
use Tuleap\Git\RetrieveGitRepository;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\ProjectByIDFactory;

final readonly class ForkCreator implements ProcessPersonalRepositoryFork, ProcessCrossProjectsRepositoryFork
{
    public function __construct(
        private GitRepositoryManager $repository_manager,
        private VerifyUserIsGitAdministrator $git_permissions_manager,
        private ProjectHistoryDao $project_history_dao,
        private ProjectByIDFactory $retrieve_project_by_id,
        private RetrieveGitRepository $retrieve_git_repository,
        private DoForkRepositoriesFormInputsBuilder $inputs_builder,
        private CheckDoForkRepositoriesCSRF $csrf_checker,
    ) {
    }

    #[\Override]
    public function processPersonalFork(PFUser $user, Project $project, ServerRequestInterface $request): Ok|Err
    {
        $this->csrf_checker->checkCSRF($project);

        if (! $user->isMember($project->getID())) {
            return Result::err(UserIsNotProjectMemberFault::build());
        }

        return $this->inputs_builder->buildForPersonalFork($request, $user)->andThen(
            fn (DoPersonalForkFormInputs $inputs) => $this->processFork(
                $user,
                $project,
                array_map(fn (string $id) => $this->retrieve_git_repository->getRepositoryById((int) $id), explode(',', $inputs->repositories_ids)),
                $inputs->fork_path,
                \GitRepository::REPO_SCOPE_INDIVIDUAL,
                $inputs->permissions,
            )
        );
    }

    #[\Override]
    public function processCrossProjectsFork(PFUser $user, ServerRequestInterface $request): Ok|Err
    {
        return $this->inputs_builder->buildForCrossProjectsFork($request)->andThen(
            function (DoCrossProjectsForkFormInputs $inputs) use ($user) {
                $project = $this->retrieve_project_by_id->getProjectById((int) $inputs->destination_project_id);
                $this->csrf_checker->checkCSRF($project);

                if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
                    return Result::err(UserIsNotGitAdminOfDestinationProjectFault::build());
                }

                return $this->processFork(
                    $user,
                    $project,
                    array_map(fn (string $id) => $this->retrieve_git_repository->getRepositoryById((int) $id), explode(',', $inputs->repositories_ids)),
                    '',
                    \GitRepository::REPO_SCOPE_PROJECT,
                    $inputs->permissions,
                );
            }
        );
    }

    /**
     * @return Ok<list<Fault>>|Err<Fault>
     */
    private function processFork(PFUser $user, Project $destination_project, array $repositories, string $namespace, string $scope, array $permissions): Ok|Err
    {
        return $this->repository_manager->forkRepositories($repositories, $destination_project, $user, $namespace, $scope, $permissions)
            ->andThen(
                function (array $warnings) use ($destination_project, $user) {
                    $this->project_history_dao->addHistory(
                        $destination_project,
                        $user,
                        new \DateTimeImmutable('now'),
                        'git_fork_repositories',
                        (string) $destination_project->getID(),
                    );

                    return Result::ok($warnings);
                }
            );
    }
}
