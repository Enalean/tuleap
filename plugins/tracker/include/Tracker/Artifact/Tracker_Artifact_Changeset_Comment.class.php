<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/date/DateHelper.class.php');

class Tracker_Artifact_Changeset_Comment {
    
    public $id;
    public $changeset;
    public $comment_type_id;
    public $canned_response_id;
    public $submitted_by;
    public $submitted_on;
    public $body;
    public $parent_id;
    
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
     * @param int                        $parent_id          The id of the parent (if comment has been modified)
     */
    public function __construct($id, 
                                $changeset, 
                                $comment_type_id, 
                                $canned_response_id, 
                                $submitted_by, 
                                $submitted_on, 
                                $body, 
                                $parent_id) {
        $this->id                 = $id;
        $this->changeset          = $changeset;
        $this->comment_type_id    = $comment_type_id;
        $this->canned_response_id = $canned_response_id;
        $this->submitted_by       = $submitted_by;
        $this->submitted_on       = $submitted_on;
        $this->body               = $body;
        $this->parent_id          = $parent_id;
    }
    
    /**
     * Returns the HTML code of this comment
     *
     * @param String  $format          Format of the output
     * @param Boolean $forMail         If the output is intended for mail notification then value should be true
     * @param Boolean $ignoreEmptyBody If true then display the user and the time even if the body is empty
     *
     * @return string the HTML code of this comment
     */
    public function fetchFollowUp($format='html', $forMail = false, $ignoreEmptyBody = false) {
        if ($ignoreEmptyBody || !empty($this->body)) {
            $uh = UserHelper::instance();
            switch ($format) {
                case 'html':
                    $html = '';
                    $hp = Codendi_HTMLPurifier::instance();
                    if ($forMail) {
                        $html .= '<div class="tracker_artifact_followup_title">'.PHP_EOL;
                        $html .= '<span class="tracker_artifact_followup_title_user">'.PHP_EOL;
                        $user = UserManager::instance()->getUserById($this->submitted_by);
                        if ($user && $user->isLoggedIn()) {
                            $html .= '<a href="mailto:'.$hp->purify($user->getEmail()).'">'.$hp->purify($user->getRealName()).' ('.$hp->purify($user->getUserName()) .')</a>'.PHP_EOL;
                        } else {
                            $html .= $GLOBALS['Language']->getText('tracker_include_artifact','anon_user').PHP_EOL;
                        }
                        $html .= '</span></div>'.PHP_EOL;
                        $timezone = '';
                        if ($user->getId() != 0) {
                            $timezone = ' ('.$user->getTimezone().')';
                        }
                        $html .= '<div class="tracker_artifact_followup_date">'. format_date($GLOBALS['Language']->getText('system', 'datefmt'), $_SERVER['REQUEST_TIME']).$timezone.'</div>'.PHP_EOL;
                        $html .= '</div>'.PHP_EOL;
                        $html .= '<div class="tracker_artifact_followup_avatar">'.PHP_EOL;
                        $html .= '<div class="avatar"></div>'.PHP_EOL;
                        $html .= '</div>'.PHP_EOL;
                        $html .= '<div class="tracker_artifact_followup_content">'.PHP_EOL;
                        $html .= '<div class="tracker_artifact_followup_comment">';
                    } else {
                        $html .= '<div class="tracker_artifact_followup_comment_edited_by">'.PHP_EOL;
                        if ($this->parent_id) {
                            $html .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'last_edited');
                            $html .= ' '. $uh->getLinkOnUserFromUserId($this->submitted_by) .' ';
                            $html .= DateHelper::timeAgoInWords($this->submitted_on, false, true);
                        }
                        $html .= '</div>'.PHP_EOL;
                    }
                    $html .= '<div class="tracker_artifact_followup_comment_body">'.PHP_EOL;
                    if ($this->parent_id && !trim($this->body)) {
                        $html .= '<em>'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'comment_cleared') .'</em>'.PHP_EOL;
                    } else {
                        $html .= $hp->purify($this->body, CODENDI_PURIFIER_BASIC, $this->changeset->artifact->getTracker()->group_id).PHP_EOL;
                    }
                    if ($forMail) {
                        $html .= '</div></div>'.PHP_EOL;
                    }
                    return $html;
                    break;
                default:
                    $output = '';
                    //if ($this->parent_id) {
                    //$output .= $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'last_edited');
                    //$output .= ' '.$uh->getDisplayNameFromUserId($this->submitted_by);
                    //$output .= ' '.DateHelper::timeAgoInWords($this->submitted_on).PHP_EOL;
                    //}
                    if ( !empty($this->body) ) {
                        $output .= PHP_EOL.PHP_EOL.$this->body.PHP_EOL.PHP_EOL;
                    }
                    return $output;
                    break;
            }
        } else {
            return null;
        }
    }
}
?>
