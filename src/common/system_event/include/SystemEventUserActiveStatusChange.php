<?php
/**
 * Copyright (c) Enalean, 2012-Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 */

namespace Tuleap\SystemEvent;

use ForgeConfig;
use PFUser;
use Project;
use SystemEvent;
use Tuleap\Project\UserRemover;
use UserGroupDao;
use UserManager;

final class SystemEventUserActiveStatusChange extends SystemEvent
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UserGroupDao
     */
    private $user_group_dao;
    /**
     * @var UserRemover
     */
    private $user_remover;

    public function injectDependencies(
        UserManager $user_manager,
        UserGroupDao $user_group_dao,
        UserRemover $user_remover,
    ): void {
        $this->user_manager   = $user_manager;
        $this->user_group_dao = $user_group_dao;
        $this->user_remover   = $user_remover;
    }

    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     * string will be html instead of plain/text
     *
     */
    public function verbalizeParameters($with_link): string
    {
        $txt  = '';
        $txt .= 'user: ' . $this->verbalizeUserId($this->getIdFromParam(), $with_link);
        return $txt;
    }

    /**
     * Process stored event
     */
    public function process()
    {
        // Check parameters
        $user_id = $this->getIdFromParam();
        $user    = $this->user_manager->getUserById($user_id);
        if ($user && ! $user->isAnonymous()) {
            $this->cleanRestrictedUserFromProjectMembershipIfNecessary($user);
            $this->done();
            return true;
        }
        return $this->setErrorBadParam();
    }

    private function cleanRestrictedUserFromProjectMembershipIfNecessary(PFUser $user): void
    {
        if (! ForgeConfig::areRestrictedUsersAllowed()) {
            return;
        }

        if (! $user->isRestricted()) {
            return;
        }

        $projects_dar = $this->user_group_dao->searchActiveProjectsByUserIdAndAccessType(
            $user->getId(),
            Project::ACCESS_PRIVATE_WO_RESTRICTED
        );

        if ($projects_dar === false) {
            return;
        }

        foreach ($projects_dar as $project_row) {
            $this->user_remover->removeUserFromProject((int) $project_row['group_id'], $user->getId());
        }
    }
}
