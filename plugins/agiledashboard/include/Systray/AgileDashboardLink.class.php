<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Systray_AgileDashboardLink extends Systray_Link {

    /**
     * @param Project                $project
     * @param Planning_ShortAccess[] $plannings
     */
    public function __construct(Project $project, array $plannings) {
        $label = $this->getLabel($project, $plannings);
        $href  = AGILEDASHBOARD_BASE_URL .'/?group_id=' . $project->getId();
        parent::__construct($label, $href);
    }

    /**
     * @param Project                $project
     * @param Planning_ShortAccess[] $plannings
     *
     * @return string | null
     */
    private function getLabel(Project $project, array $plannings) {
        $label           = $project->getPublicName();
        $milestone_title = null;
        if ($plannings) {
            $milestone_title = $this->getLatestMilestoneTitleForProject($plannings);
        }
        if ($milestone_title) {
            $label .= ': ' . $milestone_title;
        }

        return $label;
    }

    /**
     * @param Planning_ShortAccess[] $plannings
     *
     * @return string | null
     */
    private function getLatestMilestoneTitleForProject(array $plannings) {
        $latest_short_access = end($plannings);
        /*@var $latest_short_access Planning_ShortAccess[] */

        foreach ($latest_short_access->getLastOpenMilestones() as $milestone_presenter) {
            /* @var $milestone_presenter Planning_ShortAccessMilestonePresenter */
            if ($milestone_presenter->isLatest()) {
                return $milestone_presenter->getTitle();
            }
        }

        return null;
    }
}
?>
