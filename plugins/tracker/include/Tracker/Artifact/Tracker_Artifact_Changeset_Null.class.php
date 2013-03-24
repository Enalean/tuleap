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


/**
 * Fake / Null class for Tracker_Artifact_Changeset
 * 
 * This class is used when there is a need for a Changeset object but
 * not a real one (like on initialChangeset creation).
 * 
 * This class follow the Null Object Pattern
 */
class Tracker_Artifact_Changeset_Null extends Tracker_Artifact_Changeset {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(null, null, null, null, null);
    }


    /**
     * Return the value of a field in the current changeset
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return Tracker_Artifact_ChangesetValue, or null if not found
     */
    public function getValue(Tracker_FormElement_Field $field) {
        return null;
    }

    /**
     * Return the changeset values of this changeset
     *
     * @return array of Tracker_Artifact_ChangesetValue, or empty array if not found
     */
    public function getValues() {
        return null;
    }

    /**
     * fetch followup
     *
     * @param Tracker_Artifact_Changeset $previous_changeset The previous changeset
     *
     * @return string
     */
    public function fetchFollowUp($previous_changeset) {
        return '';
    }

    /**
     * Say if a user can edit a comment
     *
     * @param PFUser $user The user. If null, the current logged in user will be used.
     *
     * @return boolean true if the user can edit
     */
    public function userCanEdit(PFUser $user = null) {
        return false;
    }

    /**
     * Update the content
     *
     * @param string $body The new content
     * @param PFUser   $user The user
     *
     * @return void
     */
    public function updateComment($body, $user) {
    }

    /**
     * Delete the changeset
     *
     * @param PFUser $user the user who wants to delete the changeset
     *
     * @return void
     */
    public function delete(PFUser $user) {
    }

    /**
     * Get the comment (latest version)
     *
     * @return Tracker_Artifact_Changeset_Comment The comment of this changeset, or null if no comments
     */
    public function getComment() {
        return null;
    }

    /**
     * Returns true if there are changes in fields_data regarding this changeset, false if nothing has changed
     *
     * @param array $fields_data The data submitted (array of 'field_id' => 'value')
     *
     * @return boolean true if there are changes in fields_data regarding this changeset, false if nothing has changed
     */
    public function hasChanges($fields_data) {
        return true;
    }

    /**
     * Return diff between this changeset and previous one (HTML code)
     *
     * @return string The field difference between the previous changeset. or false if no changes
     */
    public function diffToPrevious($format='html', $user=null, $ignore_perms=false) {
        return false;
    }
    
    /**
    * Display diff messsage
    *
    * @param String $diff
    *
    */
    public function displayDiff($diff, $format, $field) {
        return false;
    }

    public function getTracker() {
        return null;
    }
    
    /**
     * Get the recipients for notification
     *
     * @param bool $is_update It is an update, not a new artifact
     *
     * @return array
     */
    public function getRecipients($is_update) {
        return array();
    }

    /**
     * Get the body for notification
     *
     * @param bool   $is_update It is an update, not a new artifact
     * @param string $recipient The recipient who will receive the notification
     *
     * @return string
     */
    public function getBodyHtml($is_update, $recipient_user, $ignore_perms=false) {
        return '';
    }

    /**
     * Get the body for notification
     *
     * @param bool   $is_update It is an update, not a new artifact
     * @param string $recipient The recipient who will receive the notification
     *
     * @return string
     */
    public function getBodyText($is_update, $recipient_user, $ignore_perms=false) {
        return '';
    }
    
    /**
     * Get the subject for notification
     *
     * @param string $recipient The recipient who will receive the notification
     *
     * @return string
     */
    public function getSubject($recipient, $ignore_perms=false) {
        return '';
    }

}
?>
