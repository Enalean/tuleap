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

use Tuleap\Tracker\Workflow\Transition\Condition\Visitor;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Workflow_Transition_Condition_Permissions extends Workflow_Transition_Condition
{
    /** @var string */
    public $identifier = 'perms';

    public const PERMISSION_TRANSITION = 'PLUGIN_TRACKER_WORKFLOW_TRANSITION';

    /** @var PermissionsManager */
    private $permission_manager;

    /** @var array */
    private $authorized_ugroups_keyname = array();

    public function __construct(Transition $transition)
    {
        parent::__construct($transition);
        $this->permission_manager = PermissionsManager::instance();
    }

    /**
     * @see Workflow_Transition_Condition::exportToXml()
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        $child = $root->addChild('condition');
        $child->addAttribute('type', $this->identifier);

        $transition_ugroups = $this->getAuthorizedUGroups();
        $grand_child = $child->addChild('permissions');
        foreach ($transition_ugroups as $transition_ugroup) {
            $ugroup_keyname = $this->getExportableUGroupKeyname($transition_ugroup['ugroup_id']);
            if ($ugroup_keyname) {
                $grand_child->addChild('permission')->addAttribute('ugroup', $ugroup_keyname);
            }
        }
    }

    public function setAuthorizedUgroupsKeyname($authorized_ugroups_keyname)
    {
        $this->authorized_ugroups_keyname = $authorized_ugroups_keyname;
    }

    /**
     * @see Workflow_Transition_Condition::saveObject()
     */
    public function saveObject()
    {
        $this->addPermissions($this->authorized_ugroups_keyname);
    }

    /**
     * Adds permissions in the database
     *
     * @param array $ugroups The list of ugroups
     *
     * @return bool
     */
    private function addPermissions($ugroups)
    {
        foreach ($ugroups as $ugroup) {
            if (! $this->permission_manager->addPermission(self::PERMISSION_TRANSITION, (int) $this->transition->getId(), $ugroup)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string or false if not exportable
     */
    private function getExportableUGroupKeyname($ugroup_id)
    {
        if ($ugroup_id < ProjectUGroup::NONE) {
            return array_search($ugroup_id, $GLOBALS['UGROUPS']);
        }
    }

    public function validate($fields_data, Tracker_Artifact $artifact, $comment_body)
    {
        $current_user = UserManager::instance()->getCurrentUser();

        if (! $this->isUserAllowedToSeeTransition($current_user, $artifact->getTracker())) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_tracker_artifact', 'transition_permissions_not_valid')
            );
            return false;
        }

        return true;
    }

    public function isUserAllowedToSeeTransition(PFUser $user, Tracker $tracker)
    {
        if ($tracker->userIsAdmin($user)) {
            return true;
        }

        $transition_ugroups = $this->getAuthorizedUGroups();
        foreach ($transition_ugroups as $ugroup) {
            if ($user->isMemberOfUGroup($ugroup['ugroup_id'], $tracker->getGroupId())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return DataAccessResult
     */
    private function getAuthorizedUGroups()
    {
        return $this->permission_manager->getAuthorizedUgroups(
            $this->transition->getId(),
            self::PERMISSION_TRANSITION
        );
    }

    /**
     * @return array
     */
    public function getAuthorizedUGroupsAsArray()
    {
        $ugroups = [];
        foreach ($this->getAuthorizedUGroups() as $ugroup) {
            $ugroups[] = $ugroup;
        }
        return $ugroups;
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visitPermissions($this);
    }
}
