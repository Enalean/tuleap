<?php
/**
 * Copyright (c) STMicroelectronics 2014. All rights reserved
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

interface Tracker_DateReminder_Role
{

    /**
     * Get the Role Label as Text
     *
     * @return String
     */
    public function getLabel();

    /**
     * Get the Role identifier
     *
     * @return String
     */
    public function getIdentifier();

    /**
     * Retrieve commentator recipients for a given artifact
     *
     *
     * @return Array of PFUser
     */
    public function getRecipientsFromArtifact(Tracker_Artifact $artifact);
}
