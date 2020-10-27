<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Admin;

use Tuleap\Layout\BaseLayout;
use Tuleap\SVN\SvnPermissionManager;
use HTTPRequest;
use CSRFSynchronizerToken;
use Project;
use ProjectUGroup;
use Feedback;
use User_ForgeUserGroupFactory;

class GlobalAdministratorsUpdater
{
    private $ugroup_factory;
    private $permissions_manager;

    public function __construct(User_ForgeUserGroupFactory $ugroup_factory, SvnPermissionManager $permissions_manager)
    {
        $this->ugroup_factory      = $ugroup_factory;
        $this->permissions_manager = $permissions_manager;
    }

    public static function generateToken(Project $project): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(SVN_BASE_URL . "/?group_id=" . $project->getid() . "&action=save-admin-groups");
    }

    public function saveAdminGroups(
        HTTPRequest $request,
        BaseLayout $layout
    ): void {
        $project          = $request->getProject();

        $token = self::generateToken($project);
        $token->check();
        $ugroups          = $request->get("admin_groups") ?: [];
        $selected_ugroups = $this->getSelectedUGroups($project, $ugroups);

        $this->permissions_manager->save($project, $selected_ugroups);
        $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-svn', 'Update successful'));

        $layout->redirect(GlobalAdministratorsController::getURL($project));
    }

    private function getSelectedUGroups(Project $project, array $ugroups): array
    {
        $groups          = [];
        $project_ugroups = $this->ugroup_factory->getAllForProject($project);

        foreach ($project_ugroups as $project_ugroup) {
            if ($project_ugroup->getId() == ProjectUGroup::ANONYMOUS) {
                continue;
            }

            if (in_array($project_ugroup->getId(), $ugroups)) {
                $groups[] = $project_ugroup->getId();
            }
        }

        return $groups;
    }
}
