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

/**
 * An agile dashboard can have several panes (MilestonePlanning, Burndown, Cardwall)
 * Each Pane is associated to a PaneInfo that describe the Pane (it's used to
 * display presence of a Pane.
 * It's meant to be as lightweight as possible as it is required to build the view
 * regardless of what we want to display.
 */
abstract class AgileDashboard_PaneInfo {

    const ACTION = 'show';

    /**
     * @var bool
     */
    private $is_active;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    protected $action = self::ACTION;

    public function __construct(Planning_Milestone $milestone) {
        $this->milestone = $milestone;
    }

    /**
     * Return true if the current Pane is selected
     *
     * @return bool
     */
    public function isActive() {
        return $this->is_active;
    }

    /**
     * Set activation
     *
     * @param bool $state
     */
    public function setActive($state) {
        $this->is_active = (bool)$state;
    }

    /**
     * Return the URI of the current pane
     *
     * @return string
     */
    public function getUri() {
        return $this->getUriForMilestone($this->milestone);
    }

    /**
     * Return the URI of the pane in another milestone (ie. I want the cardwall of Sprint 6)
     *
     * @param Planning_Milestone $milestone
     *
     * @return string
     */
    public function getUriForMilestone(Planning_Milestone $milestone) {
        return '?'. http_build_query($this->getUriParametersForMilestone($milestone));
    }

    protected function getUriParametersForMilestone(Planning_Milestone $milestone) {
        return array(
            'group_id'    => $milestone->getGroupId(),
            'planning_id' => $milestone->getPlanningId(),
            'action'      => $this->action,
            'aid'         => $milestone->getArtifactId(),
            'pane'        => $this->getIdentifier()
        );
    }

    /**
     * Return data to present an Icon to the current Pane for given milestone
     *
     * @param Planning_Milestone $milestone
     * 
     * @return Array
     */
    public function getIconTemplateParametersForMilestone(Planning_Milestone $milestone) {
        return array(
            'uri'   => $this->getUriForMilestone($milestone),
            'title' => $this->getIconTitle(),
            'icon'  => $this->getIcon(),
        );
    }

    /**
     * Technical identifier for HTML output
     *
     * @return string eg: 'cardwall'
     */
    public abstract function getIdentifier();

    /**
     * @return string eg: 'Card Wall'
     */
    public abstract function getTitle();

    /**
     * @return string eg: '/themes/common/images/ic/duck.png'
     */
    protected abstract function getIcon();

    /**
     * @return string eg: 'Access to cardwall'
     */
    protected abstract function getIconTitle();
}


?>
