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
 * Data providers for Tracker_Chart_Burndown class should implement this interface
 * to be rendered as a Burndown chart 
 */
interface Tracker_Chart_Data_IProvideDataForBurndownChart {
    /**
     * Return an array with remaining efforts
     * 
     * @return array
     */
    public function getRemainingEffort();
    /**
     * Return the first day set in DB to display in burndown
     * 
     * @return int
     */
    public function getMinDay();
    /**
     * Return the last day set in DB to display in burndown
     * 
     * @return int
     */
    public function getMaxDay();
    /**
     * Return the list of artifacts of a burndown
     * 
     * @return array
     */
    public function getArtifactIds();
}

?>
