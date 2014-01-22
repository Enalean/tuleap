<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
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

class Tracker_Artifact_Renderer_EditInPlaceRenderer extends Tracker_Artifact_EditAbstractRenderer {

    protected function fetchFormContent(Codendi_Request $request, PFUser $current_user) {
        $html  = parent::fetchFormContent($request, $current_user);
        $html .= $this->fetchFields($this->artifact, $request->get('artifact'), false);
        $html .= '<input type="hidden" name="from_overlay" value="1">';

        return $html;
    }

    /**
     * Returns HTML code to display the artifact fields
     *
     * @param array $submitted_values array of submitted values
     *
     * @return string The HTML code for artifact fields
     */
    public function fetchFields(Tracker_Artifact $artifact, $submitted_values = array()) {
        return '<div class="tabForStory1693" id="fieldsFetchedChangeMe">
            <table cellspacing="0" cellpadding="0" border="0">
                <tr valign="top">
                    <td style="padding-right:1em;">'.
                        $artifact->getTracker()->fetchFormElementsNoColumns($artifact, array($submitted_values)).
                    '</td>
                </tr>
            </table>
        </div>';
    }

    public function displayArtifactLinks($linked_artifacts) {
        $this->displayHeader();

        foreach ($linked_artifacts as $artifact) {
            /* @var $artifact Tracker_Artifact */
            $tracker_name = $artifact->getTracker()->getItemName();
            $group_id     = $artifact->getTracker()->getProject()->getID();
            $artifact_id  = $artifact->getId();

            echo '<a href="/goto?key='.$tracker_name.'&val='.$artifact_id.'&group_id='.$group_id.'" class="cross-reference">'
                    .'#' . $artifact_id . ' ' .$artifact->getTitle()
                    .'</a>';
            echo '<br />';
        }

        $this->displayFooter();
    }

    protected function displayHeader() {
        $GLOBALS['HTML']->overlay_header();
    }

    protected function enhanceRedirect(Codendi_Request $request) {
        // does nothing (there is no redirect, it's meant to be inline)
    }

    protected function displayFooter() {}
}
?>