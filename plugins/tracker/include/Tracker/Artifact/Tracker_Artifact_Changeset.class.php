<?php
/**
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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

use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsRunner;

require_once __DIR__ . '/../../../../../src/www/include/utils.php';

class Tracker_Artifact_Changeset extends Tracker_Artifact_Followup_Item
{
    public const FIELDS_ALL      = 'all';
    public const FIELDS_COMMENTS = 'comments';

    public $id;
    public $artifact;
    public $submitted_by;
    public $submitted_on;
    public $email;

    protected $values;

    /**
     * @var Tracker_Artifact_Changeset_Comment
     */
    private $latest_comment;

    /**
     * Constructor
     *
     * @param int              $id           The changeset Id
     * @param Tracker_Artifact $artifact     The artifact
     * @param int              $submitted_by The id of the owner of this changeset
     * @param int              $submitted_on The timestamp
     * @param string           $email        The email of the submitter if anonymous mode
     */
    public function __construct($id, $artifact, $submitted_by, $submitted_on, $email)
    {
        $this->id           = $id;
        $this->artifact     = $artifact;
        $this->submitted_by = $submitted_by;
        $this->submitted_on = $submitted_on;
        $this->email        = $email;
    }

    /**
     * Return the value of a field in the current changeset
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return Tracker_Artifact_ChangesetValue|null
     */
    public function getValue(Tracker_FormElement_Field $field)
    {
        if (! isset($this->values[$field->getId()])) {
            $this->values[$field->getId()] = $this->getChangesetValueFromDB($field);
        }
        return $this->values[$field->getId()];
    }

    public function canHoldValue()
    {
        return true;
    }

    private function getChangesetValueFromDB(Tracker_FormElement_Field $field)
    {
        $dar = $this->getValueDao()->searchByFieldId($this->id, $field->getId());
        if ($dar && count($dar)) {
            $row = $dar->getRow();
            return $field->getChangesetValue($this, $row['id'], $row['has_changed']);
        }
        return null;
    }

    public function setFieldValue(Tracker_FormElement_Field $field, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $this->values[$field->getId()] = $value;
    }

    public function setNoFieldValue(Tracker_FormElement_Field $field)
    {
        $this->values[$field->getId()] = false;
    }

    /**
     * Returns the submission date of this changeset (timestamp)
     *
     * @return int The submission date of this changeset (timestamp)
     */
    public function getSubmittedOn()
    {
        return $this->submitted_on;
    }

    /**
     * Returns the author of this changeset
     *
     * @return int The user id or 0/null if anonymous
     */
    public function getSubmittedBy()
    {
        return $this->submitted_by;
    }

    /**
     * Returns the author's email of this changeset
     *
     * @return string an email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Return the changeset values of this changeset
     *
     * @return Tracker_Artifact_ChangesetValue[] or empty array if not found
     */
    public function getValues()
    {
        if (! $this->values) {
            $this->forceFetchAllValues();
        }
        return $this->values;
    }

    public function forceFetchAllValues()
    {
        $this->values = array();
        $factory = $this->getFormElementFactory();
        foreach ($this->getValueDao()->searchById($this->id) as $row) {
            if ($field = $factory->getFieldById($row['field_id'])) {
                $this->values[$field->getId()] = $field->getChangesetValue($this, $row['id'], $row['has_changed']);
            }
        }
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
        if ($this->userCanDeletePermanently($user)) {
            $this->getChangesetDao()->delete($this->id);
            $this->getCommentDao()->delete($this->id);
            $this->deleteValues();
        }
    }

    protected function deleteValues()
    {
        $value_dao = $this->getValueDao();
        $factory = $this->getFormElementFactory();
        foreach ($value_dao->searchById($this->id) as $row) {
            if ($field = $factory->getFieldById($row['field_id'])) {
                $field->deleteChangesetValue($this, $row['id']);
            }
        }
        $value_dao->delete($this->id);
    }

    /**
     * Returns the ValueDao
     *
     * @return Tracker_Artifact_Changeset_ValueDao The dao
     */
    protected function getValueDao()
    {
        return new Tracker_Artifact_Changeset_ValueDao();
    }

    /**
     * Returns the Form Element Factory
     *
     * @return Tracker_FormElementFactory The factory
     */
    protected function getFormElementFactory()
    {
        return Tracker_FormElementFactory::instance();
    }

    public function getFollowUpDate()
    {
        return $this->submitted_on;
    }

    public function getFollowupContent($diff_to_previous)
    {
        $html = '';

        //The comment
        if ($comment = $this->getComment()) {
            $follow_up = $comment->fetchFollowUp();
            $html .= '<div class="tracker_artifact_followup_comment">';
            $html .= $follow_up;
            $html .= '</div>';

            if ($follow_up && $diff_to_previous) {
                $html .= '<hr size="1" />';
            }
        }

        //The changes
        if ($diff_to_previous) {
            $html .= '<ul class="tracker_artifact_followup_changes">';
            $html .= $diff_to_previous;
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Fetch followup
     *
     * @return string html
     */
    public function fetchFollowUp($diff_to_previous)
    {
        $html = '';

        $html .= $this->getAvatar();

        $html .= '<div class="tracker_artifact_followup_header">';
        $html .= $this->getPermalink();
        $html .= $this->fetchChangesetActionButtons();
        $html .= $this->getUserLink();
        $html .= $this->getTimeAgo();
        $html .= '</div>';

        // The content
        $html .= '<div class="tracker_artifact_followup_content">';
        $html .= $this->getFollowupContent($diff_to_previous);
        $html .= '</div>';

        $html .= '<div style="clear:both;"></div>';
        return $html;
    }

    private function fetchChangesetActionButtons()
    {
        $html        = '';
        $edit_button = $this->fetchEditButton();
        $mail_button = $this->fetchIncomingMailButton();

        if ($edit_button || $mail_button) {
            $html .= '<div class="tracker_artifact_followup_comment_controls">';
            $html .= $mail_button;
            $html .= ' ';
            $html .= $edit_button;
            $html .= '</div>';
        }

        return $html;
    }

    private function fetchEditButton()
    {
        if (! $this->userCanEdit()) {
            return '';
        }

        $html  = '';
        $html .= '<a href="#" class="tracker_artifact_followup_comment_controls_edit">';
        $html .= '<button class="btn btn-mini"><i class="fa fa-pencil-square-o"></i> ' . $GLOBALS['Language']->getText('plugin_tracker_fieldeditor', 'edit') . '</button>';
        $html .= '</a>';

        return $html;
    }

    private function fetchIncomingMailButton()
    {
        if (! $this->getUserManager()->getCurrentUser()->isSuperUser()) {
            return '';
        }

        $retriever = Tracker_Artifact_Changeset_IncomingMailGoldenRetriever::instance();
        $raw_mail  = $retriever->getRawMailThatCreatedChangeset($this);
        if (! $raw_mail) {
            return '';
        }

        $raw_email_button_title = $GLOBALS['Language']->getText('plugin_tracker', 'raw_email_button_title');
        $raw_mail               = Codendi_HTMLPurifier::instance()->purify($raw_mail);

        $html = '<button type="button" class="btn btn-mini tracker_artifact_followup_comment_controls_raw_email" data-raw-email="' . $raw_mail . '">
                      <i class="fa fa-envelope"></i> ' . $raw_email_button_title . '
                 </button>';

        return $html;
    }

    public function getImage()
    {
        return $GLOBALS['HTML']->getImage(
            'ic/comment.png',
            array(
                'border' => 0,
                'alt'   => 'permalink',
                'class' => 'tracker_artifact_followup_permalink',
                'style' => 'vertical-align:middle',
                'title' => 'Link to this followup - #' . (int) $this->id
            )
        );
    }

    /**
     * @return PFUser
     */
    public function getSubmitter()
    {
        if ($this->submitted_by) {
            return UserManager::instance()->getUserById($this->submitted_by);
        } else {
            $submitter = UserManager::instance()->getUserAnonymous();
            $submitter->setEmail($this->email);

            return $submitter;
        }
    }

    /**
     * @return string html
     */
    public function getSubmitterUrl()
    {
        if ($this->submitted_by) {
            $submitter = $this->getSubmitter();
            $uh = UserHelper::instance();
            $submitter_url = $uh->getLinkOnUser($submitter);
        } else {
            $hp = Codendi_HTMLPurifier::instance();
            $submitter_url = $hp->purify($this->email, CODENDI_PURIFIER_BASIC);
        }

        return $submitter_url;
    }

    /**
     * @return string
     */
    public function getHTMLAvatar()
    {
        return $this->getSubmitter()->fetchHtmlAvatar();
    }

    /**
     * @return string
     */
    public function getAvatarUrl()
    {
        return $this->getSubmitter()->getAvatarUrl();
    }

    /**
     * @return string html
     */
    public function getDateSubmittedOn()
    {
        return DateHelper::timeAgoInWords($this->submitted_on, false, true);
    }

    /**
     * @return string
     */
    public function getFollowUpClassnames($diff_to_previous)
    {
        $classnames = '';

        $comment = $this->getComment();

        if ($diff_to_previous || $this->shouldBeDisplayedAsChange($diff_to_previous, $comment)) {
            $classnames .= ' tracker_artifact_followup-with_changes ';
        }

        if ($comment && ! $comment->hasEmptyBody()) {
            $classnames .= ' tracker_artifact_followup-with_comment ';
        }

        if ($this->submitted_by && $this->submitted_by < 100) {
            $classnames .= ' tracker_artifact_followup-by_system_user ';
        }

        return $classnames;
    }


    // This function is used to cover a bug previously introduced where
    // artifacts can be updated without changes nor comment. We want to
    // display such changesets as if they were only containing changes,
    // so we introduced this function to determine wether we're in this
    // case or not.
    private function shouldBeDisplayedAsChange($changes, $comment)
    {
        if ($comment) {
            // Not comment AND no changes
            return $comment->hasEmptyBody() && ! $changes;
        }

        return true;
    }

    /**
     * Say if a user can permanently (no restore) delete a changeset
     *
     * @param PFUser $user The user who does the delete
     *
     * @return bool true if the user can delete
     */
    protected function userCanDeletePermanently(PFUser $user)
    {
        // Only tracker admin can edit a comment
        return $this->artifact->getTracker()->userIsAdmin($user);
    }

    /**
     * Say if a user can delete a changeset
     *
     * @param PFUser $user The user. If null, the current logged in user will be used.
     *
     * @return bool true if the user can delete
     */
    protected function userCanDelete(?PFUser $user = null)
    {
        if (!$user) {
            $user = $this->getUserManager()->getCurrentUser();
        }
        // Only tracker admin can edit a comment
        return $user->isSuperUser();
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
        if (!$user) {
            $user = $this->getUserManager()->getCurrentUser();
        }
        // Only tracker admin and original submitter (minus anonymous) can edit a comment
        return $this->artifact->getTracker()->userIsAdmin($user) || ((int) $this->submitted_by && $user->getId() == $this->submitted_by);
    }

    /**
     * Update the content
     *
     * @param string  $body          The new content
     * @param PFUser    $user          The user
     * @param String  $comment_format Format of the comment
     *
     * @return void
     */
    public function updateComment($body, $user, $comment_format, $timestamp)
    {
        if ($this->updateCommentWithoutNotification($body, $user, $comment_format, $timestamp)) {
            $this->executePostCreationActions();
        }
    }

    public function updateCommentWithoutNotification($body, $user, $comment_format, $timestamp)
    {
        if ($this->userCanEdit($user)) {
            $commentUpdated = $this->getCommentDao()->createNewVersion(
                $this->id,
                $body,
                $user->getId(),
                $timestamp,
                $this->getComment()->id,
                $comment_format
            );

            unset($this->latest_comment);

            if ($commentUpdated) {
                $reference_manager = $this->getReferenceManager();
                $reference_manager->extractCrossRef(
                    $body,
                    $this->artifact->getId(),
                    Tracker_Artifact::REFERENCE_NATURE,
                    $this->artifact->getTracker()->getGroupID(),
                    $user->getId(),
                    $this->artifact->getTracker()->getItemName()
                );

                $params = array('group_id'     => $this->getArtifact()->getTracker()->getGroupId(),
                                'artifact'     => $this->getArtifact(),
                                'changeset_id' => $this->getId(),
                                'text'         => $body);

                EventManager::instance()->processEvent('tracker_followup_event_update', $params);

                return true;
            }
        }

        return false;
    }

    /**
     * @return ReferenceManager
     */
    protected function getReferenceManager()
    {
        return ReferenceManager::instance();
    }

    /**
     * Get the comment (latest version)
     *
     * @return Tracker_Artifact_Changeset_Comment The comment of this changeset, or null if no comments
     */
    public function getComment()
    {
        if (isset($this->latest_comment)) {
            return $this->latest_comment;
        }

        if ($row = $this->getCommentDao()->searchLastVersion($this->id)->getRow()) {
            $this->latest_comment = new Tracker_Artifact_Changeset_Comment(
                $row['id'],
                $this,
                $row['comment_type_id'],
                $row['canned_response_id'],
                $row['submitted_by'],
                $row['submitted_on'],
                $row['body'],
                $row['body_format'],
                $row['parent_id']
            );
        }
        return $this->latest_comment;
    }

    /**
     *
     * @param Tracker_Artifact_Changeset_Comment|-1 $comment
     */
    public function setLatestComment($comment)
    {
        $this->latest_comment = $comment;
    }

    /**
     * Return the ChangesetDao
     *
     * @return Tracker_Artifact_ChangesetDao The Dao
     */
    protected function getChangesetDao()
    {
        return new Tracker_Artifact_ChangesetDao();
    }

    /**
     * Returns the comment dao
     *
     * @return Tracker_Artifact_Changeset_CommentDao The dao
     */
    protected function getCommentDao()
    {
        return new Tracker_Artifact_Changeset_CommentDao();
    }

    /**
     * @param array $fields_data The data submitted (array of 'field_id' => 'value')
     *
     * @return bool true if there are changes in fields_data regarding this changeset, false if nothing has changed
     */
    public function hasChanges(array $fields_data)
    {
        $has_changes = false;
        $used_fields = $this->getFormElementFactory()->getUsedFields($this->artifact->getTracker());
        foreach ($used_fields as $field) {
            if (is_a($field, 'Tracker_FormElement_Field_ReadOnly')) {
                continue;
            }

            $is_field_part_of_submitted_data = array_key_exists($field->id, $fields_data);
            if (! $is_field_part_of_submitted_data) {
                continue;
            }

            $current_value = $this->getValue($field);
            if ($current_value) {
                $has_changes = $field->hasChanges($this->getArtifact(), $current_value, $fields_data[$field->id]);
            } else {
                //There is no current value in the changeset for the submitted field
                //It means that the field has been added afterwards.
                //Then consider that there is at least one change (the value of the new field).
                $has_changes = true;
            }

            if ($has_changes) {
                break;
            }
        }

        return $has_changes;
    }

    /**
     * Return mail format diff between this changeset and previous one (HTML code)
     *
     * @return string The field difference between the previous changeset. or false if no changes
     */
    public function mailDiffToPrevious($format = 'html', $user = null, $ignore_perms = false)
    {
        return $this->diffToPrevious($format, $user, $ignore_perms, true);
    }

    /**
     * Return modal format diff between this changeset and previous one (HTML code)
     *
     * @return string The field difference between the previous changeset. or false if no changes
     */
    public function modalDiffToPrevious($format = 'html', $user = null, $ignore_perms = false)
    {
        return $this->diffToPrevious($format, $user, $ignore_perms, false, true);
    }

    /**
     * Return diff between this followup and previous one (HTML code)
     *
     * @return string html
     */
    public function diffToPrevious(
        $format = 'html',
        $user = null,
        $ignore_perms = false,
        $for_mail = false,
        $for_modal = false
    ) {
        $result             = '';
        $factory            = $this->getFormElementFactory();
        $previous_changeset = $this->getArtifact()->getPreviousChangeset($this->getId());

        if (! $previous_changeset) {
            return $result;
        }

        $this->forceFetchAllValues();
        foreach ($this->getValues() as $field_id => $current_changeset_value) {
            $field = $factory->getFieldById($field_id);
            if (! $field) {
                continue;
            }

            if ((! $ignore_perms && ! $field->userCanRead($user) ) || ! $current_changeset_value) {
                continue;
            }

            if (! $current_changeset_value->hasChanged()) {
                continue;
            }

            $previous_changeset_value = $previous_changeset->getValue($field);

            if (! $previous_changeset_value) {//Case : field added later (ie : artifact already exists) => no value
                $diff = $current_changeset_value->nodiff($format);
            } elseif ($for_mail) {
                $artifact_id  = $this->getArtifact()->getId();
                $changeset_id = $this->getId();

                $diff = $current_changeset_value->mailDiff(
                    $previous_changeset_value,
                    $artifact_id,
                    $changeset_id,
                    $ignore_perms,
                    $format,
                    $user
                );
            } elseif ($for_modal) {
                $diff = $current_changeset_value->modalDiff($previous_changeset_value, $format, $user);
            } else {
                $diff = $current_changeset_value->diff($previous_changeset_value, $format, $user);
            }

            if ($diff) {
                $result .= $this->displayDiff($diff, $format, $field);
            }
        }
        return $result;
    }

    public function diffToPreviousArtifactView(PFUser $user, Tracker_Artifact_Followup_Item $previous_item)
    {
        $result = '';

        foreach ($this->getValues() as $current_changeset_value) {
            if ($current_changeset_value === null) {
                continue;
            }
            $field = $current_changeset_value->getField();
            if (! $current_changeset_value->hasChanged() || ! $field->userCanRead($user)) {
                continue;
            }

            $previous_changeset_value = $this->getPreviousChangesetValue($previous_item, $field);

            if ($previous_changeset_value === null) {
                $diff = $current_changeset_value->nodiff('html');
            } else {
                $diff = $current_changeset_value->diff($previous_changeset_value, 'html', $user);
            }

            if ($diff) {
                $result .= $this->displayDiff($diff, 'html', $field);
            }
        }
        return $result;
    }

    /**
     * @return null|Tracker_Artifact_ChangesetValue
     */
    private function getPreviousChangesetValue(
        Tracker_Artifact_Followup_Item $previous_item,
        Tracker_FormElement_Field $field
    ) {
        if ($previous_item->canHoldValue()) {
            return $previous_item->getValue($field);
        }

        $previous_changeset = $this->getArtifact()->getPreviousChangeset($this->getId());
        if ($previous_changeset !== null) {
            return $previous_changeset->getValue($field);
        }

        return null;
    }

    /**
    * Display diff messsage
    *
    * @param String $diff
    *
    */
    public function displayDiff($diff, $format, $field)
    {
        $result = false;
        switch ($format) {
            case 'html':
                $result .= '<li>';
                $result .= '<span class="tracker_artifact_followup_changes_field"><b>' . Codendi_HTMLPurifier::instance()->purify($field->getLabel()) . '</b></span> ';
                $result .= '<span class="tracker_artifact_followup_changes_changes">' . $diff . '</span>';
                $result .= '</li>';
                break;
            default://text
                $result .= ' * ' . $field->getLabel() . ' : ' . PHP_EOL;
                $result .= $diff . PHP_EOL;
                break;
        }
        return $result;
    }

    /**
     * Get an instance of UserManager
     *
     * @return UserManager
     */
    public function getUserManager()
    {
        return UserManager::instance();
    }

    public function getTracker()
    {
        return $this->getArtifact()->getTracker();
    }

    public function executePostCreationActions()
    {
        ActionsRunner::build(BackendLogger::getDefaultLogger())->executePostCreationActions($this);
    }

    /**
     * Return the Tracker_Artifact of this changeset
     *
     * @return Tracker_Artifact The artifact of this changeset
     */
    public function getArtifact()
    {
        return $this->artifact;
    }

    /**
     * Returns the Id of this changeset
     *
     * @return int The Id of this changeset
     */
    public function getId()
    {
        return $this->id;
    }

    public function getRESTValue(PFUser $user, $fields)
    {
        $comment = $this->getComment();
        if (! $comment) {
            $comment = new Tracker_Artifact_Changeset_CommentNull($this);
        }
        if ($fields == self::FIELDS_COMMENTS && $comment->hasEmptyBody()) {
            return null;
        }
        $classname_with_namespace = 'Tuleap\Tracker\REST\ChangesetRepresentation';
        $changeset_representation = new $classname_with_namespace;
        $changeset_representation->build(
            $this,
            $comment,
            $fields  == self::FIELDS_COMMENTS  ? array() : $this->getRESTFieldValues($user)
        );
        return $changeset_representation;
    }

    private function getRESTFieldValues(PFUser $user)
    {
        $values = array();
        $factory = $this->getFormElementFactory();

        foreach ($factory->getUsedFieldsForREST($this->getTracker()) as $field) {
            if ($field && $field->userCanRead($user)) {
                $values[] = $field->getRESTValue($user, $this);
            }
        }
        return array_filter($values);
    }

    /**
     * Returns a REST representation with all fields content.
     * This does not check permissions so use it with caution.
     *
     * "A great power comes with a great responsibility"
     *
     * @return \Tuleap\Tracker\REST\ChangesetRepresentation
     */
    public function getFullRESTValue(PFUser $user)
    {
        $comment = $this->getComment();
        if (! $comment) {
            $comment = new Tracker_Artifact_Changeset_CommentNull($this);
        }

        $changeset_representation = new Tuleap\Tracker\REST\ChangesetRepresentation();
        $changeset_representation->build(
            $this,
            $comment,
            $this->getFullRESTFieldValuesWithoutPermissions($user)
        );

        return $changeset_representation;
    }

    private function getFullRESTFieldValuesWithoutPermissions(PFUser $user)
    {
        $values = array();
        $factory = $this->getFormElementFactory();

        foreach ($factory->getUsedFieldsForREST($this->getTracker()) as $field) {
            $values[] = $field->getRESTValue($user, $this);
        }

        return array_values(array_filter($values));
    }

    /**
     * Link to changeset in interface
     *
     * @return String
     */
    public function getUri()
    {
        return TRACKER_BASE_URL . '/?aid=' . $this->getArtifact()->getId() . '#followup_' . $this->getId();
    }

    /**
     * @return bool
     */
    public function isLastChangesetOfArtifact()
    {
        $artifact_last_changeset = $this->artifact->getLastChangeset();

        if (! $artifact_last_changeset) {
            return false;
        }

        return $this->id === $artifact_last_changeset->getId();
    }
}
