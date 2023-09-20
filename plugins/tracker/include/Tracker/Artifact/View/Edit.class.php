<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Mail\Transport\MailTransportBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\Artifact\RichTextareaProvider;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;

class Tracker_Artifact_View_Edit extends Tracker_Artifact_View_View
{
    public const USER_PREFERENCE_DISPLAY_CHANGES = 'tracker_artifact_comment_display_changes';
    public const USER_PREFERENCE_INVERT_ORDER    = 'tracker_comment_invertorder';

    /**
     * @var Tracker_Artifact_ArtifactRenderer
     */
    protected $renderer;

    public function __construct(
        Artifact $artifact,
        Codendi_Request $request,
        PFUser $user,
        Tracker_Artifact_ArtifactRenderer $renderer,
    ) {
        parent::__construct($artifact, $request, $user);

        $this->renderer = $renderer;
    }

    /** @see Tracker_Artifact_View_View::getURL() */
    public function getURL()
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'aid' => $this->artifact->getId(),
            ]
        );
    }

    /** @see Tracker_Artifact_View_View::getTitle() */
    public function getTitle()
    {
        return dgettext('tuleap-tracker', 'Artifact');
    }

    /** @see Tracker_Artifact_View_View::getIdentifier() */
    public function getIdentifier()
    {
        return 'edit';
    }

    /** @see Tracker_Artifact_View_View::fetch() */
    public function fetch()
    {
        $html  = '';
        $html .= '<div class="tracker_artifact">';

        if (! $this->artifact->getLastChangeset()) {
            $html .= "<div class='feedback_error'>" . dgettext('tuleap-tracker', 'The artifact is not linked to any changeset.') . "</div>";
        }
        if ($this->artifact->userCanUpdate($this->user)) {
            self::fetchEditViewJSCode();
        }

        $submitted_values = $this->request->get('artifact');
        if (! $submitted_values || ! is_array($submitted_values)) {
            $submitted_values = [];
        }
        $html_form  = $this->renderer->fetchFields($this->artifact, $submitted_values);
        $html_form .= $this->fetchFollowUps($this->request->get('artifact_followup_comment'));

        $html .= $this->renderer->fetchArtifactForm($html_form);
        $html .= '</div>';

        return $html;
    }

    final protected static function fetchEditViewJSCode(): void
    {
        $include_assets = new \Tuleap\Layout\IncludeAssets(
            __DIR__ . '/../../../../scripts/artifact/frontend-assets',
            '/assets/trackers/artifact'
        );
        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('edit-view.js'));
    }

    /**
     * Returns HTML code to display the artifact follow-up comments
     *
     * @return string The HTML code for artifact follow-up comments
     */
    private function fetchFollowUps($submitted_comment = '')
    {
        $html  = '';
        $html .= $this->fetchSubmitButton();

        $tracker      = $this->artifact->getTracker();
        $invert_order = $this->user->getPreference(self::USER_PREFERENCE_INVERT_ORDER . '_' . $tracker->getId()) == false;

        $classname       = 'tracker_artifact_followup_comments-display_changes';
        $display_changes = $this->user->getPreference(self::USER_PREFERENCE_DISPLAY_CHANGES);
        if ($display_changes !== false && $display_changes == 0) {
            $classname = '';
        }

        $html .= '<div id="tracker_artifact_followup_comments" class="' . $classname . '">';
        $html .= '<div id="tracker_artifact_followup_comments-content">';
        $html .= $this->fetchSettingsButton($invert_order, $display_changes);
        $html .= '<h1 id="tracker_artifact_followups">' . dgettext('tuleap-tracker', 'Follow-ups') . '</h1>';
        $html .= '<section class="tracker_artifact_followups" data-test="artifact-followups">';

        $comments = $this->artifact->getFollowupsContent();
        if ($invert_order) {
            $html .= $this->fetchAddNewComment($tracker, $submitted_comment);
            $html .= $this->fetchCommentContent($comments, true);
        } else {
            $html .= $this->fetchCommentContent($comments, false);
            $html .= $this->fetchAddNewComment($tracker, $submitted_comment);
        }

        $html .= '</section>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</td></tr></table>'; //see fetchFields

        return $html;
    }

    private function fetchSettingsButton($invert_order, $display_changes)
    {
        $settings_label        = dgettext('tuleap-tracker', 'Display settings');
        $invert_comment_label  = dgettext('tuleap-tracker', 'Comments are in reversed order');
        $display_changes_label = dgettext('tuleap-tracker', 'Changes are displayed');

        $invert_order_style = '';
        if (! $invert_order) {
            $invert_order_style = 'style="display: none"';
        }

        $display_changes_style = '';
        if (! $display_changes) {
            $display_changes_style = 'style="display: none"';
        }

        $html  = '<div class="tracker_artifact_followup_comments_display_settings">';
        $html .= '<div class="btn-group">';
        $html .= '<a href="#" class="btn btn-small dropdown-toggle" data-toggle="dropdown">';
        $html .= '<i class="fa fa-cog"></i> ' . $settings_label . ' <span class="caret"></span>';
        $html .= '</a>';
        $html .= '<ul class="dropdown-menu pull-right">';
        $html .= '<li>';
        $html .= '<a href="#invert-order" id="invert-order-menu-item">';
        $html .= '<i class="fa fa-check" ' . $invert_order_style . '></i> ' . $invert_comment_label;
        $html .= '</a>';
        $html .= '</li>';
        $html .= '<li>';
        $html .= '<a href="#" id="display-changes-menu-item">';
        $html .= '<i class="fa fa-check"  ' . $display_changes_style . '></i> ' . $display_changes_label;
        $html .= '</a>';
        $html .= '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function fetchCommentContent(array $comments, $invert_comments)
    {
        $html = '';
        $i    = 0;

        $previous_item    = null;
        $comments_content = [];

        foreach ($comments as $item) {
            \assert($item instanceof Tracker_Artifact_Followup_Item);
            if ($previous_item) {
                $comment_html = $item->getFollowUpHTML($this->user, $previous_item);
                if ($comment_html !== null) {
                    $comments_content[] = $comment_html;
                }
            }
            $previous_item = $item;
        }

        if ($invert_comments) {
            $comments_content = array_reverse($comments_content);
        }

        return implode('', $comments_content);
    }

    private function fetchAddNewComment(Tracker $tracker, $submitted_comment)
    {
        $html = '<div>';
        $hp   = Codendi_HTMLPurifier::instance();

        if (count($responses = $tracker->getCannedResponseFactory()->getCannedResponses($tracker))) {
            $html .= '<p><b>' . dgettext('tuleap-tracker', 'Use a Canned Response:') . '</b>&nbsp;';
            $html .= '<select id="tracker_artifact_canned_response_sb">';
            $html .= '<option selected="selected" value="">--</option>';
            foreach ($responses as $r) {
                $html .= '<option value="' .  $hp->purify($r->body, CODENDI_PURIFIER_CONVERT_HTML) . '">' .  $hp->purify($r->title, CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
            }
            $html .= '</select>';
            $html .= '<noscript> javascript must be enabled to use this feature! </noscript>';
            $html .= '</p>';
        }

        if ($this->artifact->userCanUpdate($this->user)) {
            $rich_textarea_provider = new RichTextareaProvider(
                TemplateRendererFactory::build(),
                new \Tuleap\Tracker\Artifact\UploadDataAttributesForRichTextEditorBuilder(
                    new FileUploadDataProvider(
                        new FrozenFieldDetector(
                            new TransitionRetriever(
                                new StateFactory(
                                    TransitionFactory::instance(),
                                    new SimpleWorkflowDao()
                                ),
                                new TransitionExtractor()
                            ),
                            FrozenFieldsRetriever::instance(),
                        ),
                        Tracker_FormElementFactory::instance()
                    )
                )
            );

            $html .= $rich_textarea_provider->getTextarea(
                $tracker,
                $this->artifact,
                $this->user,
                'tracker_followup_comment_new',
                'artifact_followup_comment',
                8,
                80,
                $submitted_comment,
                false,
            );
            $html .= $this->fetchReplyByMailHelp();
        }

        $html .= '</div>';

        return $html;
    }

    private function fetchReplyByMailHelp()
    {
        $html = '';
        if ($this->canUpdateArtifactByMail()) {
            $email = Codendi_HTMLPurifier::instance()->purify($this->artifact->getInsecureEmailAddress());
            $html .= '<p class="email-tracker-help"><i class="fa fa-info-circle"></i> ';
            $html .= sprintf(dgettext('tuleap-tracker', 'You can also reply to this artifact <a href="#" class="email-tracker email-tracker-reply" data-email="%1$s"><span>by email</span></a>.'), $email);
            $html .= '</p>';
        }

        return $html;
    }

    /**
     * @return Tracker_ArtifactByEmailStatus
     */
    private function canUpdateArtifactByMail()
    {
        $config = new MailGatewayConfig(
            new MailGatewayConfigDao(),
            MailTransportBuilder::getPlatformMailConfiguration(),
        );

        $status = new Tracker_ArtifactByEmailStatus($config);

        return $status->canUpdateArtifactInInsecureMode($this->artifact->getTracker());
    }

    private function fetchSubmitButton(): string
    {
        if ($this->artifact->userCanUpdate($this->user) && $this->artifact->getLastChangeset()) {
            return $this->renderer->fetchSubmitButton($this->user);
        }

        return "";
    }
}
