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

class Tracker_Artifact_ReadOnlyRenderer extends Tracker_Artifact_EditRenderer
{

    /**
     * @return string The HTML code for artifact fields
     */
    public function fetchFields(Tracker_Artifact $artifact, array $submitted_values)
    {
        $submitted_artifact = '';

        if (! empty($submitted_values)) {
            $submitted_artifact = "submitted_artifact";
        }

        return "<div class='" . $submitted_artifact . "'>" . $artifact->getTracker()->fetchFormElementsReadOnly($artifact, $submitted_values) . "</div>";
    }
}
