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

abstract class AgileDashboard_PaneInfo {
    /**
     * @var bool
     */
    private $is_active;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    public function __construct(Planning_Milestone $milestone) {
        $this->milestone = $milestone;
    }

    /**
     * @return bool
     */
    public function isActive() {
        return $this->is_active;
    }

    /**
     * @param bool $state
     */
    public function setActive($state) {
        $this->is_active = (bool)$state;
    }

    public function getUri() {
        return $this->getUriForMilestone($this->milestone);
    }

    public function getUriForMilestone(Planning_Milestone $milestone) {
        return '?group_id='.$milestone->getGroupId().'&planning_id='.$milestone->getPlanningId().'&action=show&aid='.$milestone->getArtifactId();
    }

    public function getIconTemplateParametersForMilestone(Planning_Milestone $milestone) {
        return array(
            'uri'   => $this->getUriForMilestone($milestone),
            'title' => $this->getIconTitle(),
            'icon'  => $this->getIcon(),
        );
    }

    /**
     * @return string eg: 'cardwall'
     */
    public abstract function getIdentifier();

    /**
     * @return string eg: 'Card Wall'
     */
    public abstract function getTitle();

    /**
     * @see string eg: '/themes/common/images/ic/duck.png'
     */
    public abstract function getIcon();

    /**
     * @return string eg: 'Access to cardwall'
     */
    public abstract function getIconTitle();
}


?>
