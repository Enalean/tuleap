<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Artifact_View_Edit extends Tracker_Artifact_View_View {

    /** @see Tracker_Artifact_View_View::getURL() */
    public function getURL() {
        return TRACKER_BASE_URL .'/?'. http_build_query(
            array(
                'aid' => $this->artifact->getId(),
            )
        );
    }

    /** @see Tracker_Artifact_View_View::getLabel() */
    public function getLabel() {
        return 'Artifact';
    }

    /** @see Tracker_Artifact_View_View::getIdentifier() */
    public function getIdentifier() {
        return 'edit';
    }

    /** @see Tracker_Artifact_View_View::fetch() */
    public function fetch() {
        $html  = '';

        $html .= $this->fetchFields($this->request->get('artifact'));

        $html .= $this->fetchFollowUps($this->request->get('artifact_followup_comment'));

        // We don't need History since we have changesets
        //$html .= $this->_fetchHistory();
        return $html;
    }

    /**
     * Returns HTML code to display the artifact fields
     *
     * @param array $submitted_values array of submitted values
     *
     * @return string The HTML code for artifact fields
     */
    private function fetchFields($submitted_values = array()) {
        return
            '<div class="tabForStory1693" id="fieldsFetchedChangeMe">
                <table cellspacing="0" cellpadding="0" border="0">
                    <tr valign="top">
                        <td style="padding-right:1em;">'.
                            $this->artifact->getTracker()->fetchFormElements($this->artifact, array($submitted_values)).
                        '</td>
                    </tr>
                </table>
            </div>';
    }

    /**
     * Returns HTML code to display the artifact follow-up comments
     *
     * @param PFUser $current_user the current user
     *
     * @return string The HTML code for artifact follow-up comments
     */
    private function fetchFollowUps($submitted_comment = '') {
        $html = '';

        $html_submit_button = '<p style="text-align:center;">';
        $html_submit_button .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $html_submit_button .= ' ';
        $html_submit_button .= '<input type="submit" name="submit_and_stay" value="'. $GLOBALS['Language']->getText('global', 'btn_submit_and_stay') .'" />';
        $html_submit_button .= '</p>';

        $html .= $html_submit_button;

        $html .= '<fieldset id="tracker_artifact_followup_comments"><legend
                          class="'. Toggler::getClassName('tracker_artifact_followups', true, true) .'"
                          id="tracker_artifact_followups">'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','follow_ups').'</legend>';
        $html .= '<ul class="tracker_artifact_followups">';
        $previous_changeset = null;
        $i = 0;
        foreach ($this->artifact->getChangesets() as $changeset) {
            if ($previous_changeset) {
                $html .= '<li id="followup_'. $changeset->id .'" class="'. html_get_alt_row_color($i++) .' tracker_artifact_followup">';
                $html .= $changeset->fetchFollowUp($previous_changeset);
                $html .= '</li>';
            }
            $previous_changeset = $changeset;
        }

        $html .= '<li>';
        $html .= '<div class="'. html_get_alt_row_color($i++) .'">';
        $hp = Codendi_HTMLPurifier::instance();

        if (count($responses = $this->artifact->getTracker()->getCannedResponseFactory()->getCannedResponses($this->artifact->getTracker()))) {
            $html .= '<p><b>' . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'use_canned') . '</b>&nbsp;';
            $html .= '<select id="tracker_artifact_canned_response_sb">';
            $html .= '<option selected="selected" value="">--</option>';
            foreach ($responses as $r) {
                $html .= '<option value="'.  $hp->purify($r->body, CODENDI_PURIFIER_CONVERT_HTML) .'">'.  $hp->purify($r->title, CODENDI_PURIFIER_CONVERT_HTML) .'</option>';
            }
            $html .= '</select>';
            $html .= '<noscript> javascript must be enabled to use this feature! </noscript>';
            $html .= '</p>';
        }
        $html .= '<b>'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'add_comment') .'</b><br />';
        $html .= '<textarea id="tracker_followup_comment_new" wrap="soft" rows="12" cols="80" style="width:99%;" name="artifact_followup_comment" id="artifact_followup_comment">'. $hp->purify($submitted_comment, CODENDI_PURIFIER_CONVERT_HTML).'</textarea>';
        $html .= '</div>';

        if ($this->user->isAnonymous()) {
            $html .= $this->fetchAnonymousEmailForm();
        }
        $html .= '</li>';

        $html .= '</ul>';
        $html .= '</fieldset>';

        $html .= $html_submit_button;

        $html .= '</td></tr></table>'; //see fetchFields

        return $html;
    }

    protected function fetchAnonymousEmailForm() {
        $html = '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact', 'not_logged_in', array('/account/login.php?return_to='.urlencode($_SERVER['REQUEST_URI'])));
        $html .= '<br />';
        $html .= '<input type="text" name="email" id="email" size="50" maxsize="100" />';
        $html .= '</p>';
        return $html;
    }
}
