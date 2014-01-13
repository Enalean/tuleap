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

class Tracker_Artifact_ReadOnlyRenderer extends Tracker_Artifact_EditRenderer {

    /**
     * Returns HTML code to display the artifact fields
     *
     * @param array $submitted_values array of submitted values
     *
     * @return string The HTML code for artifact fields
     */
    public function fetchFields(Tracker_Artifact $artifact, $submitted_values = array()) {
        return '<div class="tabForStory1693" id="fieldsFetchedChangeMe">
            <input type="hidden" id="artifact-read-only-page" value="" />

            '. $this->addEditButton($artifact) .'

            <table cellspacing="0" cellpadding="0" border="0">
                <tr valign="top">
                    <td style="padding-right:1em;">'.
                        $artifact->getTracker()->fetchFormElementsReadOnly($artifact, array($submitted_values)).
                    '</td>
                </tr>
            </table>

            '. $this->addEditButton($artifact) .'

        </div>';
    }

    private function addEditButton(Tracker_Artifact $artifact) {
        return '<p>
                    <a href="'.TRACKER_BASE_URL.'/?aid='. $artifact->getId() .'&func=edit" class="btn btn-primary">
                        <i class="icon-edit"></i> '.
                        $GLOBALS['Language']->getText('plugin_tracker_include_report' ,'edit').
                    '</a>
                </p>';
    }
}

?>
