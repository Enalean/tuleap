<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * This class builds Planning_ShortAccess
 */
class Planning_ShortAccessFactory {

    /** @var AgileDashboard_PaneInfoFactory */
    private $pane_info_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    public function __construct(
        PlanningFactory $planning_factory,
        AgileDashboard_PaneInfoFactory $pane_info_factory
    ) {
        $this->planning_factory  = $planning_factory;
        $this->pane_info_factory = $pane_info_factory;
    }

    /**
     * Get a list of planning short access defined in a group_id
     *
     * @param PFUser $user     The user who will see the planning
     * @param int  $group_id
     *
     * @return array of Planning_ShortAccess
     */
    public function getPlanningsShortAccess(PFUser $user, $group_id, Planning_MilestoneFactory $milestone_factory, $theme_path) {
        $plannings    = $this->planning_factory->getPlannings($user, $group_id);
        $short_access = array();
        foreach ($plannings as $planning) {
            if ($planning->getBacklogTracker()->isDeleted()) {
                throw new Planning_InvalidConfigurationException($GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_invalidconf_deletedtracker', array($planning->getPlanTitle(), $planning->getBacklogTracker()->getName())));
            } elseif($planning->getPlanningTracker()->isDeleted()) {
                throw new Planning_InvalidConfigurationException($GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_invalidconf_deletedtracker', array($planning->getPlanTitle(), $planning->getPlanningTracker()->getName())));
            } else {
                $short_access[] = $this->getShortAccessForPlanning($planning, $user, $milestone_factory, $theme_path, 0);
            }
        }
        if (!empty($short_access)) {
            end($short_access)->setIsLatest();
        }
        return $short_access;
    }

    /**
     * @return Planning_ShortAccess
     */
    public function getShortAccessForPlanning(Planning $planning, PFUser $user, Planning_MilestoneFactory $milestone_factory, $theme_path, $offset) {
        return new Planning_ShortAccess($planning, $user, $milestone_factory, $this->pane_info_factory, $theme_path, $offset);
    }
}

?>
