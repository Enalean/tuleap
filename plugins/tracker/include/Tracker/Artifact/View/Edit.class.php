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

use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;

class Tracker_Artifact_View_Edit extends Tracker_Artifact_View_View {

    const USER_PREFERENCE_DISPLAY_CHANGES = 'tracker_artifact_comment_display_changes';
    const USER_PREFERENCE_INVERT_ORDER    = 'tracker_comment_invertorder';

    /**
     * @var Tracker_Artifact_ArtifactRenderer
     */
    protected $renderer;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(Tracker_Artifact $artifact, Codendi_Request $request, PFUser $user, Tracker_Artifact_ArtifactRenderer $renderer, EventManager $event_manager) {
        parent::__construct($artifact, $request, $user);
        $this->renderer      = $renderer;
        $this->event_manager = $event_manager;
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
        $html .= $this->fetchArtifactReferencesSidebar();
        $html .= $this->renderer->fetchFields($this->artifact, $this->request->get('artifact'));
        $html .= $this->fetchFollowUps($this->request->get('artifact_followup_comment'));
        $html .= '</div>';

        return $html;
    }

    protected function fetchArtifactReferencesSidebar() {
        $html                  = '';
        $linked_artifacts      = $this->artifact->getLinkedArtifacts($this->user);
        $reference_information = array();

        $this->event_manager->processEvent(
            TRACKER_EVENT_COMPLEMENT_REFERENCE_INFORMATION,
            array(
                'artifact' => $this->artifact,
                'reference_information'=> &$reference_information
            )
        );

        if (! empty($reference_information) || count($linked_artifacts) > 0) {
            $html .= '<div class="artifact-references">';
            $html .= '<div class="grip"><i class="icon-double-angle-left"></i></div>';
            $html .= '<div class="artifact-references-content">';

            foreach ($reference_information as $information) {
                $html .= '<div>';
                $html .= '<h2>' . $information['title'] . '</h2>';
                foreach ($information['links'] as $link) {
                    $html .= '<img src="' . $link['icon'] . '"/>';
                    $html .= '<a href="' . $link['link'] . '">' . $link['label'] . '</a>';
                }
                $html .= '</div>';
            }

            if (count($linked_artifacts) > 0) {
                $html .= '<div>';
                $html .= '<h2>' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'references_title') . '</h2>';
                $html .= '<ul>';

                foreach ($linked_artifacts as $artifact) {
                    $link = '/goto?key=' . $artifact->getTracker()->getItemName() . '&val=' . $artifact->getId() . '&group_id=' . $artifact->getTracker()->getProject()->getID();
                    $html .= '<li>';
                    $html .= '<a href="' . $link . '">' . $artifact->getXRefAndTitle() . '</a>';
                    $html .= '</li>';
                }

                $html .= '</ul>';
                $html .= '</div>';
            }

            $html .= '</div>';
            $html .= '</div>';
        }

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

        $tracker      = $this->artifact->getTracker();
        $invert_order = $this->user->getPreference(self::USER_PREFERENCE_INVERT_ORDER . '_' . $tracker->getId()) == false;

        $classname       = 'tracker_artifact_followup_comments-display_changes';
        $user_preference = $this->user->getPreference(self::USER_PREFERENCE_DISPLAY_CHANGES);
        if ($user_preference !== false && $user_preference == 0) {
            $classname = '';
        }

        $html .= '<div id="tracker_artifact_followup_comments" class="'. $classname .'">';
        $html .= '<div id="tracker_artifact_followup_comments-content">';
        $html .= '<h1 id="tracker_artifact_followups">'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','follow_ups').'</h1>';
        $html .= '<ul class="tracker_artifact_followups">';

        $comments = $this->artifact->getFollowupsContent();
        if ($invert_order) {
            $comments = array_reverse($comments);
            $html .= $this->fetchAddNewComment($tracker, $submitted_comment);
            $html .= $this->fetchCommentContent($comments);
        } else {
            $html .= $this->fetchCommentContent($comments);
            $html .= $this->fetchAddNewComment($tracker, $submitted_comment);
        }

        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</td></tr></table>'; //see fetchFields

        return $html;
    }

    private function fetchCommentContent(array $comments)
    {
        $html = '';
        $i    = 0;

        $previous_item = null;
        foreach ($comments as $item) {
            $diff_to_previous = $item->diffToPrevious();
            if ($previous_item) {
                $classnames  = html_get_alt_row_color($i++) .' tracker_artifact_followup ';
                $classnames .= $item->getFollowUpClassnames($diff_to_previous);
                $html .= '<li id="followup_'. $item->getId() .'" class="'. $classnames .'">';
                $html .= $item->fetchFollowUp($diff_to_previous);
                $html .= '</li>';
            }
            $previous_item = $item;
        }

        return $html;
    }

    private function fetchAddNewComment(Tracker $tracker, $submitted_comment)
    {
        $html = '<li>';
        $html .= '<div>';
        $hp = Codendi_HTMLPurifier::instance();

        if (count($responses = $tracker->getCannedResponseFactory()->getCannedResponses($tracker))) {
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
            $html .= '<textarea id="tracker_followup_comment_new" class="user-mention" wrap="soft" rows="8" cols="80" name="artifact_followup_comment" id="artifact_followup_comment">'. $hp->purify($submitted_comment, CODENDI_PURIFIER_CONVERT_HTML).'</textarea>';
            $html .= $this->fetchReplyByMailHelp();
            $html .= '</div>';
        }

        $html .= '</li>';

        return $html;
    }

    private function fetchReplyByMailHelp() {
        $html = '';
        if ($this->canUpdateArtifactByMail()) {
            $email = Codendi_HTMLPurifier::instance()->purify($this->artifact->getInsecureEmailAddress());
            $html .= '<p class="email-tracker-help"><i class="icon-info-sign"></i> ';
            $html .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'reply_by_mail_help', $email);
            $html .= '</p>';
        }

        return $html;
    }

    /**
     * @return Tracker_ArtifactByEmailStatus
     */
    private function canUpdateArtifactByMail() {
        $config = new MailGatewayConfig(
            new MailGatewayConfigDao()
        );

        $status = new Tracker_ArtifactByEmailStatus($config);

        return $status->canUpdateArtifactInInsecureMode($this->artifact->getTracker());
    }

    private function fetchSubmitButton() {
        if ($this->artifact->userCanUpdate($this->user)) {
            return $this->renderer->fetchSubmitButton($this->user);
        }
    }
}
