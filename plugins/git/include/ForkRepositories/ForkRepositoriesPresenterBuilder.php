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

namespace Tuleap\Git\ForkRepositories;

use GitPlugin;
use PFUser;
use Project;
use Tuleap\Git\RetrieveAllGitRepositories;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

final readonly class ForkRepositoriesPresenterBuilder
{
    public function __construct(
        private ProjectByIDFactory $project_by_id_factory,
        private RetrieveAllGitRepositories $retrieve_all_git_repositories,
    ) {
    }

    public function build(
        PFUser $user,
        Project $current_project,
        CSRFSynchronizerTokenInterface $csrf_token,
    ): ForkRepositoriesPresenter {
        return new ForkRepositoriesPresenter(
            $csrf_token,
            $user,
            $current_project,
            $this->buildForkableRepositories($user, $current_project),
            $this->buildForksDestinationProjects($user, $current_project),
        );
    }

    /**
     * @return list<ForkableRepositoryPresenter>
     */
    private function buildForkableRepositories(PFUser $user, Project $project): array
    {
        $repositories = [];
        foreach ($this->retrieve_all_git_repositories->getAllRepositories($project) as $repository) {
            if ($repository->getScope() === \GitRepository::REPO_SCOPE_INDIVIDUAL || ! $repository->userCanRead($user) || $repository->getDeletionDate() !== '0000-00-00 00:00:00') {
                continue;
            }

            $repositories[] = new ForkableRepositoryPresenter(
                $repository->getId(),
                $repository->getName(),
            );
        }
        return $repositories;
    }

    /**
     * @return list<ForkDestinationProjectPresenter>
     */
    private function buildForksDestinationProjects(PFUser $user, Project $current_project): array
    {
        $destination_projects = [];

        foreach (array_diff($user->getAllProjects(), [$current_project->getId()]) as $project_id) {
            $project = $this->project_by_id_factory->getProjectById($project_id);
            if ($user->isAdmin($project_id) && $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
                $destination_projects[] = new ForkDestinationProjectPresenter(
                    (int) $project->getId(),
                    $project->getIconAndPublicName(),
                    $project->getUnixNameLowerCase(),
                );
            }
        }

        return $destination_projects;
    }
}
