<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentPresenterBuilder;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\Artifact\CodeBlockFeaturesOnArtifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Artifact_Changeset_Comment
{
    /**
     * @var int|string
     */
    public $id;
    /**
     *
     * @var Tracker_Artifact_Changeset
     */
    public $changeset;
    public $comment_type_id;
    public $canned_response_id;
    /**
     * @var int | string
     */
    public $submitted_by;
    /**
     * @var int | string
     */
    public $submitted_on;
    /**
     * @var string
     */
    public $body;
    /**
     * @var string
     */
    public $bodyFormat;
    /**
     * @var int | string
     */
    public $parent_id;

    /**
     * @var array of purifier levels to be used when the comment is displayed in text/plain context
     */
    private static array $PURIFIER_LEVEL_IN_TEXT = [
        CommentFormatIdentifier::HTML->value => CODENDI_PURIFIER_STRIP_HTML,
        CommentFormatIdentifier::TEXT->value => CODENDI_PURIFIER_DISABLED,
    ];

    /**
     * @var array of purifier levels to be used when the comment is displayed in text/html context
     */
    private static array $PURIFIER_LEVEL_IN_HTML = [
        CommentFormatIdentifier::HTML->value => CODENDI_PURIFIER_FULL,
        CommentFormatIdentifier::TEXT->value => CODENDI_PURIFIER_BASIC,
    ];
    /**
     * @var ProjectUGroup[]|null
     */
    private $ugroups_can_see_private_comment;

    /**
     * Constructor
     *
     * @param int                        $id                 Changeset comment Id
     * @param Tracker_Artifact_Changeset $changeset          The associated changeset
     * @param int                        $comment_type_id    The comment type Id
     * @param int                        $canned_response_id The canned response Id
     * @param int                        $submitted_by       The Id of the user that made the comment
     * @param int                        $submitted_on       The date the comment has been done
     * @param string                     $body               The comment (aka follow-up comment)
     * @param string                     $bodyFormat         The comment type (text or html follow-up comment)
     * @param int                        $parent_id          The id of the parent (if comment has been modified)
     * @param ProjectUGroup[]|null       $ugroups_private_comment
     */
    public function __construct(
        $id,
        Tracker_Artifact_Changeset $changeset,
        $comment_type_id,
        $canned_response_id,
        $submitted_by,
        $submitted_on,
        $body,
        $bodyFormat,
        $parent_id,
        ?array $ugroups_private_comment,
    ) {
        $this->id                              = $id;
        $this->changeset                       = $changeset;
        $this->comment_type_id                 = $comment_type_id;
        $this->canned_response_id              = $canned_response_id;
        $this->submitted_by                    = $submitted_by;
        $this->submitted_on                    = $submitted_on;
        $this->body                            = $body;
        $this->bodyFormat                      = $bodyFormat;
        $this->parent_id                       = $parent_id;
        $this->ugroups_can_see_private_comment = $ugroups_private_comment;
    }

    /**
     * @return string the cleaned body to be included in a text/plain context
     */
    public function getPurifiedBodyForText(): string
    {
        return self::getCommentInPlaintext($this->getPurifier(), $this->body, CommentFormatIdentifier::fromStringWithDefault($this->bodyFormat));
    }

    private static function getCommentInPlaintext(Codendi_HTMLPurifier $purifier, string $content, CommentFormatIdentifier $comment_format): string
    {
        if ($comment_format === CommentFormatIdentifier::COMMONMARK) {
            return CommonMarkInterpreter::build($purifier)->getContentStrippedOfTags($content);
        }
        return $purifier->purify($content, self::$PURIFIER_LEVEL_IN_TEXT[$comment_format->value]);
    }

    public function getPurifiedBodyForHTML(): string
    {
        if ($this->bodyFormat === CommentFormatIdentifier::HTML->value) {
            return $this->purifyHTMLBody();
        }
        if ($this->bodyFormat === CommentFormatIdentifier::COMMONMARK->value) {
            $content_interpretor = CommonMarkInterpreter::build(
                Codendi_HTMLPurifier::instance(),
                new EnhancedCodeBlockExtension(CodeBlockFeaturesOnArtifact::getInstance())
            );

            return $content_interpretor->getInterpretedContentWithReferences(
                $this->body,
                (int) $this->changeset->getTracker()->getGroupId()
            );
        }

        $level = self::$PURIFIER_LEVEL_IN_HTML[$this->bodyFormat];
        return $this->purifyBody($level);
    }

    private function purifyBody($level): string
    {
        return $this->getPurifier()->purify(
            $this->body,
            $level,
            $this->changeset->getArtifact()->getTracker()->getGroupId()
        );
    }

    private function purifyHTMLBody(): string
    {
        return $this->getPurifier()->purifyHTMLWithReferences(
            $this->body,
            (int) $this->changeset->getArtifact()->getTracker()->getGroupId()
        );
    }

    /**
     * Returns the HTML code of this comment
     *
     * @return string the HTML code of this comment
     */
    public function fetchFollowUp(PFUser $current_user)
    {
        $presenter = self::getCommentPresenterBuilder()->getCommentPresenter($this, $current_user);

        if (! $presenter) {
            return null;
        }

        $renderer = TemplateRendererFactory::build()->getRenderer(
            __DIR__ . '/../../../templates/artifact/changeset/comment'
        );
        return $renderer->renderToString('comment', $presenter);
    }

    /**
     *
     * @return bool
     */
    public function hasEmptyBody()
    {
        return empty($this->body);
    }

    public function hasEmptyBodyForUser(PFUser $user): bool
    {
        $presenter = self::getCommentPresenterBuilder()->getCommentPresenter($this, $user);

        return $presenter === null;
    }

    private static function getCommentPresenterBuilder(): CommentPresenterBuilder
    {
        static $presenter_builder = null;
        if ($presenter_builder === null) {
            $presenter_builder = new CommentPresenterBuilder(
                new PermissionChecker(
                    new CachingTrackerPrivateCommentInformationRetriever(
                        new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao())
                    )
                ),
                UserHelper::instance()
            );
        }

        return $presenter_builder;
    }

    /**
     * Returns the HTML code of this comment
     *
     * @param String  $format Format of the output
     * @return string the HTML code of this comment
     */
    public function fetchMailFollowUp($format = CommentFormatIdentifier::HTML->value)
    {
        if ($format !== CommentFormatIdentifier::HTML->value) {
            if ($this->hasEmptyBody()) {
                return '';
            }

            $body = $this->getPurifiedBodyForText();
            return PHP_EOL . PHP_EOL . $body . PHP_EOL . PHP_EOL;
        }

        $user = $this->getCurrentUser();
        if ($user === null) {
            return '';
        }
        $avatar   = $user->fetchHtmlAvatar();
        $timezone = (! $user->isAnonymous()) ? ' (' . $user->getTimezone() . ')' : '';

        $html =
            '<tr valign="top">
                <td align="left">' .
                    $avatar . '
                </td>
                <td align="left" valign="top">
                    <div style="
                        padding:15px;
                        margin-bottom:20px;
                        margin-left:10px;
                        min-height:50px;
                        border: 1px solid #f6f6f6;
                        border-top: none;
                        -webkit-border-radius:4px;
                        border-radius:4px;
                        -moz-border-radius:4px;
                        background-color:#F6F6F6;"
                    >
                        <table style="width:100%; background-color:#F6F6F6;">
                            <tr>
                                <td>
                                    <span> ' .
                                        $this->fetchFormattedMailUserInfo($user) . '
                                    </span>
                                </td>
                                <td align="right" valign="top">
                                    <div style="text-align:right;font-size:0.95em;color:#666;">' .
                                        format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->submitted_on) .
                                        $timezone . '
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" >' .
                                    $this->fetchFormattedMailComment() . ' ' . '
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>';

        return $html;
    }

    private function fetchFormattedMailComment(): string
    {
        $formatted_comment = '';
        $comment           = '';
        if (! empty($this->body)) {
            if ($this->parent_id && ! trim($this->body)) {
                $comment =
                '<em>' . dgettext('tuleap-tracker', 'Comment has been cleared') . '</em>';
            } else {
                if ($this->parent_id) {
                    $comment .= '<em>' . dgettext('tuleap-tracker', 'Updated comment:') . '</em><br><br>';
                }
                $comment .= $this->getPurifiedBodyForHTML();
            }

            $formatted_comment = '<div style="margin: 1em 0; padding: 0.5em 1em;">' . $comment . '</div>';
        }

        return $formatted_comment;
    }

    private function fetchFormattedMailUserInfo(PFUser $user)
    {
        $hp = Codendi_HTMLPurifier::instance();

        if ($user && ! $user->isAnonymous()) {
            $user_info =
                '<a href="mailto:' . $hp->purify($user->getEmail()) . '">' .
                    $hp->purify($user->getRealName()) . ' (' . $hp->purify($user->getUserName()) . ')
                </a>';
        } else {
            $user = UserManager::instance()->getUserAnonymous();
            $user->setEmail($this->changeset->getEmail());
            $user_info = $GLOBALS['Language']->getText('tracker_include_artifact', 'anon_user');
        }

        return $user_info;
    }

    public function getSubmittedBy()
    {
        return $this->submitted_by;
    }

    public function getSubmittedOn()
    {
        return $this->submitted_on;
    }

    public function exportToXML(SimpleXMLElement $comments_node, UserXMLExporter $user_xml_exporter)
    {
        $comment_node = $comments_node->addChild('comment');

        $user_xml_exporter->exportUserByUserId($this->submitted_by, $comment_node, 'submitted_by');

        $cdata_factory = new XML_SimpleXMLCDATAFactory();
        $cdata_factory->insertWithAttributes(
            $comment_node,
            'submitted_on',
            date('c', (int) $this->submitted_on),
            ['format' => 'ISO8601']
        );

        $comment_escaped = $this->getCommentBodyWithEscapedCrossReferences();
        $cdata_factory->insert($comment_node, 'body', $comment_escaped);

        $comment_node->body['format'] = $this->bodyFormat;
        $this->exportPrivateUGroupForComment($comment_node);
    }

    private function exportPrivateUGroupForComment(SimpleXMLElement $comment_node): void
    {
        $ugroups_can_see_comment = $this->getUgroupsCanSeePrivateComment();
        if ($ugroups_can_see_comment === null) {
            return;
        }

        if (count($ugroups_can_see_comment) === 0) {
            return;
        }

        $private_ugroups = $comment_node->addChild('private_ugroups');
        foreach ($ugroups_can_see_comment as $ugroup) {
            $cdata = new XML_SimpleXMLCDATAFactory();
            $cdata->insert($private_ugroups, 'ugroup', $ugroup->getNormalizedName());
        }
    }

    private function getCommentBodyWithEscapedCrossReferences()
    {
        $reference_manager = new ReferenceManager();
        $pattern           = $reference_manager->_getExpForRef();
        $matches           = [];
        $escaped_body      = $this->body;

        if (preg_match_all($pattern, $this->body, $matches)) {
            foreach ($matches[0] as $reference) {
                $escaped_reference = str_replace('#', '# ', $reference);
                $escaped_body      = str_replace($reference, $escaped_reference, $escaped_body);
            }
        }

        return $escaped_body;
    }

    /**
     * Protected for testing purpose
     */
    protected function getCurrentUser(): ?PFUser
    {
        return UserManager::instance()->getUserById($this->submitted_by);
    }

    /**
     * Protected for testing purpose
     */
    protected function getPurifier(): Codendi_HTMLPurifier
    {
        return Codendi_HTMLPurifier::instance();
    }

    public function getChangeset(): Tracker_Artifact_Changeset
    {
        return $this->changeset;
    }

    /**
     * @return ProjectUGroup[]|null
     */
    public function getUgroupsCanSeePrivateComment(): ?array
    {
        return $this->ugroups_can_see_private_comment;
    }
}
