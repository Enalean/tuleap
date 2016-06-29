<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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
 * I'm implemented by fields that are able to produce a numerical value
 */
interface Tracker_FormElement_IComputeValues
{
    
    /**
     * Given an artifact, return a numerical value of the field for this artifact.
     *
     * @param PFUser             $user      The user who see the results
     * @param Tracker_Artifact $artifact  The artifact on which the value is computed
     * @param Integer          $timestamp Timestamp in seconds if we want a value in the past
     *
     * @return float
     */
    public function getComputedValue(PFUser $user, Tracker_Artifact $artifact, $timestamp = null);

    public function getCachedValue(PFUser $user, Tracker_Artifact $artifact, $timestamp = null);

    public function canBeUsedForLegacyAutocomputeCalculation();
}
