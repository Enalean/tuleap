<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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


/**
 * Fake / Null class for Tracker_Artifact_Changeset
 *
 * This class is used when there is a need for a Changeset object but
 * not a real one (like on initialChangeset creation).
 *
 * This class follow the Null Object Pattern
 */
class Tracker_Artifact_Changeset_Null extends Tracker_Artifact_Changeset
{
    /**
     * Constructor
     */
    public function __construct()
    {
        /** @psalm-suppress NullArgument */
        parent::__construct(0, null, null, null, null);
    }

    /**
     * Return the value of a field in the current changeset
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return null|Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getValue(Tracker_FormElement_Field $field)
    {
        return null;
    }

    /**
     * @return Tracker_Artifact_ChangesetValue[]
     */
    public function getChangesetValuesHasChanged(): array
    {
        return [];
    }

    /**
     * Return the changeset values of this changeset
     *
     * @return array of Tracker_Artifact_ChangesetValue, or empty array if not found
     */
    public function getValues()
    {
        return null;
    }

    /**
     * fetch followup
     */
    public function fetchFollowUp($diff_to_previous, PFUser $current_user): string
    {
        return '';
    }

    /**
     * Say if a user can edit a comment
     *
     * @param PFUser $user The user. If null, the current logged in user will be used.
     *
     * @return bool true if the user can edit
     */
    public function userCanEdit(?PFUser $user = null)
    {
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
    public function updateComment($body, $user, $comment_format, $timestamp)
    {
    }

    /**
     * Delete the changeset
     *
     * @param PFUser $user the user who wants to delete the changeset
     *
     * @return void
     */
    public function delete(PFUser $user)
    {
    }

    /**
     * Get the comment (latest version)
     *
     * @return Tracker_Artifact_Changeset_Comment|null The comment of this changeset, or null if no comments
     */
    public function getComment(): ?Tracker_Artifact_Changeset_Comment
    {
        return null;
    }

    /**
     * Returns true if there are changes in fields_data regarding this changeset, false if nothing has changed
     *
     * @param array $fields_data The data submitted (array of 'field_id' => 'value')
     *
     * @return bool true if there are changes in fields_data regarding this changeset, false if nothing has changed
     */
    public function hasChanges($fields_data)
    {
        return true;
    }

    /**
     * Return diff between this changeset and previous one (HTML code)
     *
     * @return string The field difference between the previous changeset. or false if no changes
     */
    public function diffToPrevious(
        $format = 'html',
        $user = null,
        $ignore_perms = false,
        $for_mail = false,
    ) {
        return false;
    }

    /**
    * Display diff messsage
    *
    * @param String $diff
    *
    */
    public function displayDiff($diff, $format, $field)
    {
        return false;
    }

    public function getTracker()
    {
        return null;
    }

    public function executePostCreationActions(bool $send_notifications): void
    {
    }
}
