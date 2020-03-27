<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\RepositoryList;

use EventManager;
use GitDao;
use GitPermissionsManager;
use PFUser;
use Project;
use Tuleap\Git\Events\GetExternalGitHomepagePluginsEvent;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;

class ListPresenterBuilder
{
    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var GitDao
     */
    private $dao;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(GitPermissionsManager $git_permissions_manager, GitDao $dao, UserManager $user_manager, EventManager $event_manager)
    {
        $this->git_permissions_manager = $git_permissions_manager;
        $this->dao                     = $dao;
        $this->user_manager            = $user_manager;
        $this->event_manager            = $event_manager;
    }

    public function build(Project $project, PFUser $current_user)
    {
        $event = new GetExternalGitHomepagePluginsEvent($project);
        $this->event_manager->processEvent($event);

        return new GitRepositoryListPresenter(
            $current_user,
            $project,
            $this->git_permissions_manager->userIsGitAdmin($current_user, $project),
            $this->getRepositoriesOwnersRepresentations($project),
            $event->getExternalPluginsInfos()
        );
    }

    /**
     *
     * @return array
     */
    protected function getRepositoriesOwnersRepresentations(Project $project)
    {
        return array_filter(
            array_map(
                function ($row) {
                    $user = $this->user_manager->getUserById($row['repository_creation_user_id']);
                    if (! $user) {
                        return;
                    }

                    $representation = new MinimalUserRepresentation();
                    $representation->build($user);

                    return $representation;
                },
                $this->dao->getProjectRepositoriesOwners(
                    $project->getID()
                )
            )
        );
    }
}
