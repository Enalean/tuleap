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

class Tracker_Artifact_Changeset_Comment
{

    /**
     * @const Changeset comment format is text.
     */
    public const TEXT_COMMENT = 'text';

    /**
     * @const Changeset comment format is HTML
     */
    public const HTML_COMMENT = 'html';

    /**
    * @const Changeset available comment formats
    */
    private static $available_comment_formats = array(
        self::TEXT_COMMENT,
        self::HTML_COMMENT,
    );

    public $id;
    /**
     *
     * @var Tracker_Artifact_Changeset
     */
    public $changeset;
    public $comment_type_id;
    public $canned_response_id;
    public $submitted_by;
    public $submitted_on;
    public $body;
    public $bodyFormat;
    public $parent_id;

    /**
     * @var array of purifier levels to be used when the comment is displayed in text/plain context
     */
    public static $PURIFIER_LEVEL_IN_TEXT = array(
        'html' => CODENDI_PURIFIER_STRIP_HTML,
        'text' => CODENDI_PURIFIER_DISABLED,
    );

    /**
     * @var array of purifier levels to be used when the comment is displayed in text/html context
     */
    public static $PURIFIER_LEVEL_IN_HTML = array(
        'html' => CODENDI_PURIFIER_FULL,
        'text' => CODENDI_PURIFIER_BASIC,
    );

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
     */
    public function __construct(
        $id,
        $changeset,
        $comment_type_id,
        $canned_response_id,
        $submitted_by,
        $submitted_on,
        $body,
        $bodyFormat,
        $parent_id
    ) {
        $this->id                 = $id;
        $this->changeset          = $changeset;
        $this->comment_type_id    = $comment_type_id;
        $this->canned_response_id = $canned_response_id;
        $this->submitted_by       = $submitted_by;
        $this->submitted_on       = $submitted_on;
        $this->body               = $body;
        $this->bodyFormat         = $bodyFormat;
        $this->parent_id          = $parent_id;
    }

    /**
     * @return string the cleaned body to be included in a text/plain context
     */
    public function getPurifiedBodyForText()
    {
        $level = self::$PURIFIER_LEVEL_IN_TEXT[$this->bodyFormat];
        return $this->purifyBody($level);
    }

    /**
     * @return string the cleaned body to be included in a text/html context
     */
    public function getPurifiedBodyForHTML()
    {
        if ($this->bodyFormat === 'html') {
            return $this->purifyHTMLBody();
        }

        $level = self::$PURIFIER_LEVEL_IN_HTML[$this->bodyFormat];
        return $this->purifyBody($level);
    }

    private function purifyBody($level)
    {
        $hp = Codendi_HTMLPurifier::instance();
        return $hp->purify(
            $this->body,
            $level,
            $this->changeset->getArtifact()->getTracker()->getGroupId()
        );
    }

    private function purifyHTMLBody()
    {
        $hp = Codendi_HTMLPurifier::instance();
        return $hp->purifyHTMLWithReferences(
            $this->body,
            $this->changeset->artifact->getTracker()->group_id
        );
    }

    /**
     * Returns the HTML code of this comment
     *
     * @return string the HTML code of this comment
     */
    public function fetchFollowUp()
    {
        if ($this->hasEmptyBody()) {
            return null;
        }

        $uh   = UserHelper::instance();
        $html = '<div class="tracker_artifact_followup_comment_edited_by">';
        if ($this->parent_id) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'last_edited');
            $html .= ' ' . $uh->getLinkOnUserFromUserId($this->submitted_by) . ' ';
            $html .= DateHelper::timeAgoInWords($this->submitted_on, false, true);
        }
        $html .= '</div>';

        if (!empty($this->body)) {
            $html .= '<input type="hidden"
                id="tracker_artifact_followup_comment_body_format_' . $this->changeset->getId() . '"
                name="tracker_artifact_followup_comment_body_format_' . $this->changeset->getId() . '"
                value="' . $this->bodyFormat . '" />';
            $html .= '<div class="tracker_artifact_followup_comment_body">';
            if ($this->parent_id && !trim($this->body)) {
                $html .= '<em>' . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'comment_cleared') . '</em>';
            } else {
                $html .= $this->getPurifiedBodyForHTML();
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     *
     * @return bool
     */
    public function hasEmptyBody()
    {
        return empty($this->body);
    }

    /**
     * Returns the HTML code of this comment
     *
     * @param String  $format Format of the output
     * @return string the HTML code of this comment
     */
    public function fetchMailFollowUp($format = 'html')
    {
        if ($format != 'html') {
            if ($this->hasEmptyBody()) {
                return '';
            }

            $body = $this->getPurifiedBodyForText();
            return PHP_EOL . PHP_EOL . $body . PHP_EOL . PHP_EOL;
        }

        $user     = UserManager::instance()->getUserById($this->submitted_by);
        $avatar   = $user->fetchHtmlAvatar();
        $timezone = ($user->getId() != 0) ? ' (' . $user->getTimezone() . ')' : '';

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

    /**
     * Check the comment format, to ensure it is in
     * a known one.
     *
     * @param string $comment_format the format of the comment
     *
     * @return string $comment_format
     */
    public static function checkCommentFormat($comment_format)
    {
        if (! in_array($comment_format, self::$available_comment_formats, $strict = true)) {
            $comment_format = Tracker_Artifact_Changeset_Comment::TEXT_COMMENT;
        }

        return $comment_format;
    }

    private function fetchFormattedMailComment()
    {
        $formatted_comment = '';
        if (!empty($this->body)) {
            if ($this->parent_id && !trim($this->body)) {
                $comment =
                '<em>' .
                    $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'comment_cleared') . '
                </em>';
            } else {
                $comment = $this->getPurifiedBodyForHTML();
            }

            $formatted_comment = '<div style="margin: 1em 0; padding: 0.5em 1em;">' . $comment . '</div>';
        }

        return $formatted_comment;
    }

    private function fetchFormattedMailUserInfo(PFUser $user)
    {
        $hp = Codendi_HTMLPurifier::instance();

        if ($user && !$user->isAnonymous()) {
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

        $cdata_factory   = new XML_SimpleXMLCDATAFactory();
        $cdata_factory->insertWithAttributes(
            $comment_node,
            'submitted_on',
            date('c', $this->submitted_on),
            ['format' => 'ISO8601']
        );

        $comment_escaped = $this->getCommentBodyWithEscapedCrossReferences();
        $cdata_factory->insert($comment_node, 'body', $comment_escaped);

        $comment_node->body['format'] = $this->bodyFormat;
    }

    private function getCommentBodyWithEscapedCrossReferences()
    {
        $reference_manager = new ReferenceManager();
        $pattern           = $reference_manager->_getExpForRef();
        $matches           = array();
        $escaped_body      = $this->body;

        if (preg_match_all($pattern, $this->body, $matches)) {
            foreach ($matches[0] as $reference) {
                $escaped_reference = str_replace('#', '# ', $reference);
                $escaped_body      = str_replace($reference, $escaped_reference, $escaped_body);
            }
        }

        return $escaped_body;
    }
}
