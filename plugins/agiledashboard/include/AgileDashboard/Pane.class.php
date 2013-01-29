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

/**
 * Base interface to display a content pane in the agiledashboard next to a
 * milestone
 */
abstract class AgileDashboard_Pane {

    /**
     * @see AgileDashboard_PaneInfo::getUriForMilestone()
     * @return string URI of the pane for a given milestone
     */
    public abstract function getUriForMilestone(Planning_Milestone $milestone);

    /**
     * @see AgileDashboard_PaneInfo::getIdentifier()
     * @return string eg: 'cardwall'
     */
    public abstract function getIdentifier();

    /**
     * Return the content when displayed as a Pane
     *
     * @return string eg: '<a href="">customize</a> <table>...</table>'
     */
    public abstract function getFullContent();

    /**
     * Return the content when displayed on the agile dashboard front page
     * Only used for cardwall as of today
     *
     * @return string eg: '<table>...</table>'
     */
    public abstract function getMinimalContent();

}
?>
