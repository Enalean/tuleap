<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Pane;

use Planning_Milestone;

/**
 * An agile dashboard can have several panes (MilestonePlanning, Burndown, Cardwall)
 * Each Pane is associated to a PaneInfo that describe the Pane (it's used to
 * display presence of a Pane.
 * It's meant to be as lightweight as possible as it is required to build the view
 * regardless of what we want to display.
 */
abstract class PaneInfo
{
    public const ACTION = 'show';

    /**
     * @var bool
     */
    private $is_active;

    /**
     * @var Planning_Milestone
     */
    protected $milestone;

    protected $action = self::ACTION;

    public function __construct(Planning_Milestone $milestone)
    {
        $this->milestone = $milestone;
    }

    /**
     * Return true if the current Pane is selected
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * @return bool
     */
    public function isExternalLink()
    {
        return false;
    }

    /**
     * Set activation
     *
     * @param bool $state
     */
    public function setActive($state)
    {
        $this->is_active = (bool) $state;
    }

    /**
     * Return the URI of the current pane
     *
     * @return string
     */
    public function getUri()
    {
        return AGILEDASHBOARD_BASE_URL . '/?' .
            http_build_query(
                [
                    'group_id'    => $this->milestone->getGroupId(),
                    'planning_id' => $this->milestone->getPlanningId(),
                    'action'      => $this->action,
                    'aid'         => $this->milestone->getArtifactId(),
                    'pane'        => $this->getIdentifier()
                ]
            );
    }

    /**
     * Technical identifier for HTML output
     *
     * @return string eg: 'cardwall'
     */
    abstract public function getIdentifier();

    /**
     * @return string eg: 'Card Wall'
     */
    abstract public function getTitle();

    /**
     * @return string eg: 'fa-table'
     */
    abstract public function getIconName();
}
