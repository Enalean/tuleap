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

class Workflow_Transition_Condition_Permissions {

    const PERMISSION_TRANSITION = 'PLUGIN_TRACKER_WORKFLOW_TRANSITION';
    const CONDITION_TYPE        = 'perms';

    /** @var Transition */
    protected $transition;

    public function __construct(Transition $transition) {
        $this->transition = $transition;
    }

    /**
     * Get the html code needed to display the condition in workflow admin
     *
     * @return string html
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
     * Export postactions date to XML
     *
     * @param SimpleXMLElement &$root     the node to which the postaction is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(&$root, $xmlMapping) {
        $root->addAttribute('type', self::CONDITION_TYPE);

        $pm = $this->getPermissionsManager();
        $transition_ugroups = $pm->getAuthorizedUgroups($this->transition->getId(), self::PERMISSION_TRANSITION);

        if ($transition_ugroups) {
            $child = $root->addChild('permissions');
            foreach ($transition_ugroups as $transition_ugroup) {
                if (($ugroup = array_search($transition_ugroup['ugroup_id'], $GLOBALS['UGROUPS'])) !== false && $transition_ugroup['ugroup_id'] < 100) {
                    $child->addChild('permission')->addAttribute('ugroup', $ugroup);
                }
            }
        }
    }

    /**
     * Wrapper for PermissionsManager
     *
     * @return PermissionsManager
     */
    public function getPermissionsManager() {
        return PermissionsManager::instance();
    }
}
?>
