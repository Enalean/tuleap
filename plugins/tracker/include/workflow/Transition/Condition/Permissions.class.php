<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 
require_once(dirname(__FILE__) . '/../Condition.class.php');

class Workflow_Transition_Condition_Permissions extends Workflow_Transition_Condition {

    const PERMISSION_TRANSITION = 'PLUGIN_TRACKER_WORKFLOW_TRANSITION';
    const CONDITION_TYPE        = 'perms';

    /** @var PermissionsManager */
    private $permission_manager;

    public function __construct(Transition $transition) {
        parent::__construct($transition);
        $this->permission_manager = PermissionsManager::instance();
    }

    /**
     * @see Workflow_Transition_Condition::fetch()
     */
    public function fetch() {
        $html  = '';
        $html .= $GLOBALS['Language']->getText('workflow_admin','label_define_transition_permissions');
        $html .= '<br />';
        $html .= '<p>';
        $html .= plugin_tracker_permission_fetch_selection_field(
            self::PERMISSION_TRANSITION,
            $this->transition->getId(),
            $this->transition->getGroupId()
        );
        $html .= '</p>';
        return $html;
    }

    /**
     * @see Workflow_Transition_Condition::exportToXml()
     */
    public function exportToXml(&$root, $xmlMapping) {
        $root->addAttribute('type', self::CONDITION_TYPE);

        $transition_ugroups = $this->permission_manager->getAuthorizedUgroups($this->transition->getId(), self::PERMISSION_TRANSITION);
        $child = $root->addChild('permissions');
        foreach ($transition_ugroups as $transition_ugroup) {
            $ugroup_keyname = $this->getExportableUGroupKeyname($transition_ugroup['ugroup_id']);
            if ($ugroup_keyname) {
                $child->addChild('permission')->addAttribute('ugroup', $ugroup_keyname);
            }
        }
    }

    /**
     * @see Workflow_Transition_Condition::saveObject()
     */
    public function saveObject() {
        $permissions = $this->transition->getPermissions();
        $this->addPermissions($permissions);
    }

    /**
     * Adds permissions in the database
     *
     * @param array $ugroups The list of ugroups
     *
     * @return boolean
     */
    private function addPermissions($ugroups) {
        foreach ($ugroups as $ugroup) {
            if (! $this->permission_manager->addPermission(self::PERMISSION_TRANSITION, (int)$this->transition->getId(), $ugroup)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string or false if not exportable
     */
    private function getExportableUGroupKeyname($ugroup_id) {
        if ($ugroup_id < 100) {
            return array_search($ugroup_id, $GLOBALS['UGROUPS']);
        }
    }
}
?>
