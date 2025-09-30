<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Project\Duplication\DuplicationUserGroupMapping;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class Workflow_Transition_Condition_Permissions_Factory
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(UGroupManager $ugroup_manager)
    {
        $this->ugroup_manager = $ugroup_manager;
    }

    /**
     * @return Workflow_Transition_Condition_Permissions
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition, Project $project)
    {
        $authorized_ugroups_keyname = [];
        if (isset($xml->permissions)) {
            foreach ($xml->permissions->permission as $perm) {
                $ugroup = (string) $perm['ugroup'];
                if (isset($GLOBALS['UGROUPS'][$ugroup])) {
                    $authorized_ugroups_keyname[] = $GLOBALS['UGROUPS'][$ugroup];
                } else {
                    $ugroup = $this->ugroup_manager->getUGroupByName($project, $ugroup);
                    if ($ugroup) {
                        $authorized_ugroups_keyname[] = $ugroup->getId();
                    }
                }
            }
        }
        $condition = new Workflow_Transition_Condition_Permissions($transition);
        $condition->setAuthorizedUgroupsKeyname($authorized_ugroups_keyname);
        return $condition;
    }

    public function duplicate(Transition $from_transition, $new_transition_id, DuplicationUserGroupMapping $duplication_user_group_mapping): void
    {
        PermissionsManager::instance()->duplicatePermissions(
            $from_transition->getId(),
            $new_transition_id,
            [Workflow_Transition_Condition_Permissions::PERMISSION_TRANSITION],
            $duplication_user_group_mapping,
        );
    }
}
