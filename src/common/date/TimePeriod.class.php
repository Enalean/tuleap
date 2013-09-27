<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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
 * A time period that has a start date and a duration
 */
interface TimePeriod {

    /**
     * @return int
     */
    function getStartDate();

    /**
     * @return int
     */
    function getDuration();

    /**
     * @return int
     */
    function getEndDate();

    /**
     * @return array of string
     */
    function getHumanReadableDates();

    /**
     * To be used to iterate consistently over the time period
     *
     * @return array of int
     */
    function getDayOffsets();
}
