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

    const USER_PREFERENCE_DISPLAY_CHANGES = 'tracker_artifact_comment_display_changes';

    /**
     * @var Tracker_Artifact_ArtifactRenderer
     */
    private $renderer;

    public function __construct(Tracker_Artifact $artifact, Codendi_Request $request, PFUser $user, Tracker_Artifact_ArtifactRenderer $renderer) {
        parent::__construct($artifact, $request, $user);
        $this->renderer = $renderer;
    }

    /** @see Tracker_Artifact_View_View::getURL() */
    public function getURL() {
        return TRACKER_BASE_URL .'/?'. http_build_query(
            array(
                'aid' => $this->artifact->getId(),
            )
        );
    }

    /** @see Tracker_Artifact_View_View::getTitle() */
    public function getTitle() {
        return $GLOBALS['Language']->getText('plugin_tracker_artifact', 'edit_title');
    }

    /** @see Tracker_Artifact_View_View::getIdentifier() */
    public function getIdentifier() {
        return 'edit';
    }

    /** @see Tracker_Artifact_View_View::fetch() */
    public function fetch() {
        $html  = '';
        $html .= '<div class="tracker_artifact">';
        $html .= $this->renderer->fetchFields($this->artifact, $this->request->get('artifact'));
        $html .= $this->fetchFollowUps($this->request->get('artifact_followup_comment'));
        $html .= '</div>';

        return $html;
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

        $html .= $this->fetchSubmitButton();

        $classname = 'tracker_artifact_followup_comments-display_changes';
        $user_preference = $this->user->getPreference(self::USER_PREFERENCE_DISPLAY_CHANGES);
        if ($user_preference !== false && $user_preference == 0) {
            $classname = '';
        }
        $html .= '<fieldset id="tracker_artifact_followup_comments" class="'. $classname .'"><legend
                          class="'. Toggler::getClassName('tracker_artifact_followups', true, true) .'"
                          id="tracker_artifact_followups">'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','follow_ups').'</legend>';
        $html .= '<ul class="tracker_artifact_followups">';
        $previous_changeset = null;
        $i = 0;
        foreach ($this->artifact->getChangesets() as $changeset) {
            if ($previous_changeset) {
                $classnames  = html_get_alt_row_color($i++) .' tracker_artifact_followup ';
                $classnames .= $changeset->getFollowUpClassnames();
                $html .= '<li id="followup_'. $changeset->id .'" class="'. $classnames .'">';
                $html .= $changeset->fetchFollowUp();
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

        if ($this->artifact->userCanUpdate($this->user)) {
            $html .= '<b>'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'add_comment') .'</b><br />';
            $html .= '<textarea id="tracker_followup_comment_new" wrap="soft" rows="12" cols="80" style="width:99%;" name="artifact_followup_comment" id="artifact_followup_comment">'. $hp->purify($submitted_comment, CODENDI_PURIFIER_CONVERT_HTML).'</textarea>';
            $html .= '</div>';
        }

        $html .= '</li>';

        $html .= '</ul>';
        $html .= '</fieldset>';

        $html .= '</td></tr></table>'; //see fetchFields

        return $html;
    }

    private function fetchSubmitButton() {
        if ($this->artifact->userCanUpdate($this->user)) {
            return $this->renderer->fetchSubmitButton();
        }
    }
}
?>
