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

/**
 * I'm responsible of rendering artifact to user (creation, update, view...)
 */
abstract class Tracker_Artifact_ArtifactRenderer {
    /**
     * @var Tracker
     */
    protected $tracker;

    /**
     * @var EventManager
     */
    protected $event_manager;

    /**
     * @var Tracker_Artifact_Redirect
     */
    protected $redirect;

    public function __construct(Tracker $tracker, EventManager $event_manager) {
        $this->tracker            = $tracker;
        $this->event_manager      = $event_manager;
        $this->redirect           = new Tracker_Artifact_Redirect();
        $this->redirect->base_url = TRACKER_BASE_URL;
    }

    /**
     * Render artifact form
     *
     * @param Codendi_Request $request
     * @param PFUser $current_user
     */
    public function display(Codendi_Request $request, PFUser $current_user) {
        $this->enhanceRedirect($request);

        $this->displayHeader();
        echo $this->fetchArtifactEditForm(
            $this->redirect->toUrl(),
            $this->fetchFormContent($request, $current_user)
        );
        $this->displayFooter();
    }

    /**
     * Render everything before artifact form
     */
    abstract protected function displayHeader();

    /**
     * Render everything after artifact form
     */
    abstract protected function displayFooter();

    /**
     * Render artifact form content
     *
     * @param Codendi_Request $request
     * @param PFUser $current_user
     */
    abstract protected function fetchFormContent(Codendi_Request $request, PFUser $current_user);

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
                        $artifact->getTracker()->fetchFormElements($artifact, array($submitted_values)).
                    '</td>
                </tr>
            </table>
        </div>';
    }

    /**
     * Returns HTML code to display the submit buttons
     *
     * @return string The HTML code for submit buttons
     */
    public function fetchSubmitButton() {
        return '<p style="text-align:center;">
                  <input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />
                  <input type="submit" name="submit_and_stay" value="'. $GLOBALS['Language']->getText('global', 'btn_submit_and_stay') .'" />
                </p>';
    }

    /**
     * Return HTML code to display an input for anonymous user email
     *
     * @return string
     */
    public function fetchAnonymousEmailForm() {
        $html = '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact', 'not_logged_in', array('/account/login.php?return_to='.urlencode($_SERVER['REQUEST_URI'])));
        $html .= '<br />';
        $html .= '<input type="text" name="email" id="email" size="50" maxsize="100" />';
        $html .= '</p>';
        return $html;
    }

    private function fetchArtifactEditForm($url, $html) {
        return '
        <form action="'.$url.'" method="POST" enctype="multipart/form-data">
            <input type="hidden" value="67108864" name="max_file_size" />
            '.$html.'
        </form>
        '.$this->fetchRulesAsJavascript();
    }


    protected function fetchRulesAsJavascript() {
        return $this->tracker->displayRulesAsJavascript();
    }

    protected function enhanceRedirect(Codendi_Request $request) {
        $this->event_manager->processEvent(
            TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION,
            array(
                'request'  => $request,
                'redirect' => $this->redirect,
            )
        );
    }
}

?>
