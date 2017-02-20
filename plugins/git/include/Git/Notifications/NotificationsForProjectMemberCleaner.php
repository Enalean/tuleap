<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\Git\Notifications;

use Git_PostReceiveMailManager;
use GitRepositoryFactory;
use PFUser;
use Project;

class NotificationsForProjectMemberCleaner
{
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var Git_PostReceiveMailManager
     */
    private $mail_to_be_notified_manager;

    public function __construct(
        GitRepositoryFactory $repository_factory,
        Git_PostReceiveMailManager $mail_to_be_notified_manager
    ) {
        $this->repository_factory          = $repository_factory;
        $this->mail_to_be_notified_manager = $mail_to_be_notified_manager;
    }

    public function cleanNotificationsAfterUserRemoval(Project $project, PFUser $user)
    {
        if ($user->isMember($project->getID())) {
            return;
        }

        $repositories = $this->repository_factory->getAllRepositories($project);
        foreach ($repositories as $repository) {
            if (! $repository->userCanRead($user)) {
                $this->mail_to_be_notified_manager->removeMailByRepository($repository, $user->getEmail());
            }
        }
    }
}
