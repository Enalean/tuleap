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

use PFUser;
use Project;
use Tuleap\Request\CSRFSynchronizerTokenInterface;

final readonly class ForkRepositoriesPresenter
{
    public string $user_name;
    public string $post_url;
    public bool $is_user_project_member_of_current_project;
    public bool $can_user_fork_repositories;
    public bool $can_user_choose_fork_destination;
    public bool $has_forkable_repositories;
    public bool $has_user_projects;
    public int $project_id;

    /**
     * @param list<ForkableRepositoryPresenter> $forkable_repositories
     * @param list<ForkDestinationProjectPresenter> $user_projects
     */
    public function __construct(
        public CSRFSynchronizerTokenInterface $csrf_token,
        public PFUser $user,
        public Project $project,
        public array $forkable_repositories,
        public array $user_projects,
    ) {
        $this->is_user_project_member_of_current_project = $user->isMember((int) $project->getId());
        $this->has_forkable_repositories                 = $forkable_repositories !== [];
        $this->has_user_projects                         = $user_projects !== [];
        $this->user_name                                 = $user->getUserName();
        $this->post_url                                  = ForkRepositoriesUrlsBuilder::buildPOSTForksPermissionsURL($project);
        $this->project_id                                = (int) $project->getID();
        $this->can_user_choose_fork_destination          = $this->is_user_project_member_of_current_project || $this->has_user_projects;
        $this->can_user_fork_repositories                = $this->has_forkable_repositories && $this->can_user_choose_fork_destination;
    }
}
