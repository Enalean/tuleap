<?php
/*
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

class Tracker_Artifact_EditOverlayRenderer extends Tracker_Artifact_EditAbstractRenderer {

    protected function fetchFormContent(Codendi_Request $request, PFUser $current_user) {
        $html  = '';
        $html .= $this->fetchFields($this->artifact, $request->get('artifact'));
        $html .= $this->fetchSubmitButton();
        return $html;
    }

    protected function enhanceRedirect(Codendi_Request $request) {
        // does nothing (there is no redirect, it's meant to be inline)
    }

    protected function displayHeader() {
        $GLOBALS['HTML']->overlay_header();
    }

    protected function displayFooter() {
        $GLOBALS['HTML']->overlay_footer();
    }
}

?>
