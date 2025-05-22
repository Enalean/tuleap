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

namespace Tuleap\Tracker\Artifact\View;

use Codendi_HTMLPurifier;
use Codendi_Request;
use PFUser;
use TemplateRendererFactory;
use Tracker;
use Tracker_Artifact_ArtifactRenderer;
use Tracker_Artifact_Followup_Item;
use Tracker_ArtifactByEmailStatus;
use Tracker_FormElementFactory;
use TransitionFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\EditView\NewCommentPresenter;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\Artifact\RichTextareaConfiguration;
use Tuleap\Tracker\Artifact\RichTextareaProvider;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;

readonly class ArtifactViewEdit extends TrackerArtifactView
{
    public const USER_PREFERENCE_DISPLAY_CHANGES = 'tracker_artifact_comment_display_changes';
    public const USER_PREFERENCE_INVERT_ORDER    = 'tracker_comment_invertorder';

    public function __construct(
        Artifact $artifact,
        Codendi_Request $request,
        PFUser $user,
        protected Tracker_Artifact_ArtifactRenderer $renderer,
    ) {
        parent::__construct($artifact, $request, $user);
    }

    /** @see TrackerArtifactView::getURL() */
    public function getURL(): string
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'aid' => $this->artifact->getId(),
            ]
        );
    }

    /** @see TrackerArtifactView::getTitle() */
    public function getTitle(): string
    {
        return dgettext('tuleap-tracker', 'Artifact');
    }

    /** @see TrackerArtifactView::getIdentifier() */
    public function getIdentifier(): string
    {
        return 'edit';
    }

    /** @see TrackerArtifactView::fetch() */
    public function fetch(): string
    {
        $html  = '';
        $html .= '<div class="tracker_artifact">';

        if (! $this->artifact->getLastChangeset()) {
            $html .= "<div class='feedback_error'>" . dgettext('tuleap-tracker', 'The artifact is not linked to any changeset.') . '</div>';
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
        $include_assets = new \Tuleap\Layout\IncludeViteAssets(
            __DIR__ . '/../../../scripts/artifact/frontend-assets',
            '/assets/trackers/artifact'
        );
        $GLOBALS['HTML']->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptViteAsset($include_assets, 'src/edition/edit-view.ts')
        );
    }

    /**
     * Returns HTML code to display the artifact follow-up comments
     *
     * @return string The HTML code for artifact follow-up comments
     */
    private function fetchFollowUps(string $submitted_comment = ''): string
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
        $html .= $this->fetchSettingsButton($invert_order, (bool) $display_changes);
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

    private function fetchSettingsButton(bool $invert_order, bool $display_changes): string
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
        $html .= '<a class="btn btn-small dropdown-toggle" data-toggle="dropdown">';
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

    private function fetchCommentContent(array $comments, bool $invert_comments): string
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

    private function fetchAddNewComment(Tracker $tracker, string $submitted_comment): string
    {
        $html = '<div class="artifact-new-comment-section">';
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
            $renderer_factory = TemplateRendererFactory::build();

            $renderer               = $renderer_factory->getRenderer(__DIR__ . '/../../Artifact');
            $rich_textarea_provider = new RichTextareaProvider(
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

            $html .= $renderer->renderToString(
                'EditView/new-comment',
                new NewCommentPresenter(
                    $tracker,
                    $rich_textarea_provider->getTextarea(
                        RichTextareaConfiguration::fromNewFollowUpComment(
                            $tracker,
                            $this->artifact,
                            $this->user,
                            $submitted_comment
                        ),
                        false
                    )
                )
            );
            $html .= $this->fetchReplyByMailHelp();
        }

        $html .= '</div>';

        return $html;
    }

    private function fetchReplyByMailHelp(): string
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

    private function canUpdateArtifactByMail(): bool
    {
        $config = new MailGatewayConfig(
            new MailGatewayConfigDao(),
        );

        $status = new Tracker_ArtifactByEmailStatus($config);

        return $status->canUpdateArtifactInInsecureMode($this->artifact->getTracker());
    }

    private function fetchSubmitButton(): string
    {
        if ($this->artifact->userCanUpdate($this->user) && $this->artifact->getLastChangeset()) {
            return $this->renderer->fetchSubmitButton($this->user);
        }

        return '';
    }
}
