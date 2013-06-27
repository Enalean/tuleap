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

class Tracker_Artifact_SubmitRenderer extends Tracker_Artifact_SubmitAbstractRenderer {

    /**
     * @var Tracker_IDisplayTrackerLayout
     */
    private $layout;

    public function __construct(Tracker $tracker, EventManager $event_manager, Tracker_IDisplayTrackerLayout $layout) {
        parent::__construct($tracker, $event_manager);
        $this->layout = $layout;
    }

    protected function fetchFormContent(Codendi_Request $request, PFUser $current_user) {
        return $this->fetchNewArtifactForm($request, $current_user);
    }

    protected function displayHeader() {
        $breadcrumbs = array(
            array(
                'title' => 'New artifact',
                'url'   => $this->tracker->getSubmitUrl(),
            ),
        );

        $this->tracker->displayHeader($this->layout, $this->tracker->name, $breadcrumbs);
        echo $this->fetchSubmitInstructions();
    }

    private function fetchNewArtifactForm(Codendi_Request $request, PFUser $current_user) {
        $html = '';

        $html .= $this->fetchFormElements($request, $current_user);

        $html .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $html .= ' ';
        $html .= '<input type="submit" name="submit_and_continue" value="'. $GLOBALS['Language']->getText('global', 'btn_submit_and_continue') .'" />';
        $html .= '<input type="submit" name="submit_and_stay" value="'. $GLOBALS['Language']->getText('global', 'btn_submit_and_stay') .'" />';

        return $html;
    }

    protected function displayFooter() {
        $this->tracker->displayFooter($this->layout);
    }

}
?>
