<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017. All rights reserved
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

use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;

require_once('common/date/DateHelper.class.php');
require_once('common/mail/MailManager.class.php');
require_once('common/language/BaseLanguageFactory.class.php');
require_once('utils.php');

class Tracker_Artifact_Changeset extends Tracker_Artifact_Followup_Item {
    const DEFAULT_MAIL_SENDER = 'forge__artifacts';

    const FIELDS_ALL      = 'all';
    const FIELDS_COMMENTS = 'comments';

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
    public function __construct($id, $artifact, $submitted_by, $submitted_on, $email) {
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
     * @return Tracker_Artifact_ChangesetValue, or null if not found
     */
    public function getValue(Tracker_FormElement_Field $field) {
        if (! isset($this->values[$field->getId()])) {
            $this->values[$field->getId()] = $this->getChangesetValueFromDB($field);
        }
        return $this->values[$field->getId()];
    }

    private function getChangesetValueFromDB(Tracker_FormElement_Field $field) {
        $dar = $this->getValueDao()->searchByFieldId($this->id, $field->getId());
        if ($dar && count($dar)) {
            $row = $dar->getRow();
            return $field->getChangesetValue($this, $row['id'], $row['has_changed']);
        }
        return null;
    }

    public function setFieldValue(Tracker_FormElement_Field $field, Tracker_Artifact_ChangesetValue $value = null) {
        $this->values[$field->getId()] = $value;
    }

    /**
     * Returns the submission date of this changeset (timestamp)
     *
     * @return int The submission date of this changeset (timestamp)
     */
    public function getSubmittedOn() {
        return $this->submitted_on;
    }

    /**
     * Returns the author of this changeset
     *
     * @return int The user id or 0/null if anonymous
     */
    public function getSubmittedBy() {
        return $this->submitted_by;
    }

    /**
     * Returns the author's email of this changeset
     *
     * @return string an email
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Return the changeset values of this changeset
     *
     * @return Tracker_Artifact_ChangesetValue[] or empty array if not found
     */
    public function getValues() {
        if (! $this->values) {
            $this->forceFetchAllValues();
        }
        return $this->values;
    }

    public function forceFetchAllValues() {
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
    public function delete(PFUser $user) {
        if ($this->userCanDeletePermanently($user)) {
            $this->getChangesetDao()->delete($this->id);
            $this->getCommentDao()->delete($this->id);
            $this->deleteValues();
        }
    }

    protected function deleteValues() {
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
    protected function getValueDao() {
        return new Tracker_Artifact_Changeset_ValueDao();
    }

    /**
     * Returns the Form Element Factory
     *
     * @return Tracker_FormElementFactory The factory
     */
    protected function getFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }

    public function getFollowUpDate() {
        return $this->submitted_on;
    }

    public function getFollowupContent() {
        $html = '';

        //The comment
        if ($comment = $this->getComment()) {
            $html .= '<div class="tracker_artifact_followup_comment">';
            $html .= $comment->fetchFollowUp();
            $html .= '</div>';

            if ($comment->fetchFollowUp() && $this->diffToPrevious()) {
                $html .= '<hr size="1" />';
            }
        }

        //The changes
        if ($changes = $this->diffToPrevious()) {
            $html .= '<ul class="tracker_artifact_followup_changes">';
            $html .= $changes;
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Fetch followup
     *
     * @return string html
     */
    public function fetchFollowUp() {
        $html = '';

        $html .= $this->getAvatarIfEnabled();

        $html .= '<div class="tracker_artifact_followup_header">';
        $html .= $this->getPermalink();
        $html .= $this->fetchChangesetActionButtons();
        $html .= $this->getUserLink();
        $html .= $this->getTimeAgo();
        $html .= '</div>';

        // The content
        $html .= '<div class="tracker_artifact_followup_content">';
        $html .= $this->getFollowupContent();
        $html .= '</div>';

        $html .= '<div style="clear:both;"></div>';
        return $html;
    }

    private function fetchChangesetActionButtons() {
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

    private function fetchEditButton() {
        if (! $this->userCanEdit()) {
            return '';
        }

        $html  = '';
        $html .= '<a href="#" class="tracker_artifact_followup_comment_controls_edit">';
        $html .= '<button class="btn btn-mini"><i class="icon-edit"></i> ' . $GLOBALS['Language']->getText('plugin_tracker_fieldeditor', 'edit') . '</button>';
        $html .= '</a>';

        return $html;
    }

    private function fetchIncomingMailButton() {
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

        $html = '<button type="button" class="btn btn-mini tracker_artifact_followup_comment_controls_raw_email" data-raw-email="'. $raw_mail .'">
                      <i class="icon-envelope"></i> '. $raw_email_button_title .'
                 </button>';

        return $html;
    }

    public function getImage() {
        return $GLOBALS['HTML']->getImage(
            'ic/comment.png',
            array(
                'border' => 0,
                'alt'   => 'permalink',
                'class' => 'tracker_artifact_followup_permalink',
                'style' => 'vertical-align:middle',
                'title' => 'Link to this followup - #'. (int) $this->id
            )
        );
    }

    /**
     * @return PFUser
     */
    public function getSubmitter() {
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
    public function getSubmitterUrl() {
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
    public function getHTMLAvatar() {
        return $this->getSubmitter()->fetchHtmlAvatar();
    }

    /**
     * @return string
     */
    public function getAvatarUrl() {
        return $this->getSubmitter()->getAvatarUrl();
    }

    /**
     * @return string html
     */
    public function getDateSubmittedOn() {
        return DateHelper::timeAgoInWords($this->submitted_on, false, true);
    }

    /**
     * @return string
     */
    public function getFollowUpClassnames() {
        $classnames = '';

        $comment = $this->getComment();
        $changes = $this->diffToPrevious();

        if ($changes || $this->shouldBeDisplayedAsChange($changes, $comment)) {
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
    private function shouldBeDisplayedAsChange($changes, $comment) {
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
     * @return boolean true if the user can delete
     */
    protected function userCanDeletePermanently(PFUser $user) {
        // Only tracker admin can edit a comment
        return $this->artifact->getTracker()->userIsAdmin($user);
    }

    /**
     * Say if a user can delete a changeset
     *
     * @param PFUser $user The user. If null, the current logged in user will be used.
     *
     * @return boolean true if the user can delete
     */
    protected function userCanDelete(PFUser $user = null) {
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
     * @return boolean true if the user can edit
     */
    public function userCanEdit(PFUser $user = null) {
        if (!$user) {
            $user = $this->getUserManager()->getCurrentUser();
        }
        // Only tracker admin and original submitter (minus anonymous) can edit a comment
        return $this->artifact->getTracker()->userIsAdmin($user) || ((int)$this->submitted_by && $user->getId() == $this->submitted_by);
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
    public function updateComment($body, $user, $comment_format, $timestamp) {
        if ($this->updateCommentWithoutNotification($body, $user, $comment_format, $timestamp)) {
            $this->notify();
        }
    }

    public function updateCommentWithoutNotification($body, $user, $comment_format, $timestamp) {
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
    protected function getReferenceManager() {
        return ReferenceManager::instance();
    }

    /**
     * Get the comment (latest version)
     *
     * @return Tracker_Artifact_Changeset_Comment The comment of this changeset, or null if no comments
     */
    public function getComment() {
        if (isset($this->latest_comment)) {
            return $this->latest_comment;
        }

        if ($row = $this->getCommentDao()->searchLastVersion($this->id)->getRow()) {
            $this->latest_comment = new Tracker_Artifact_Changeset_Comment($row['id'],
                                                    $this,
                                                    $row['comment_type_id'],
                                                    $row['canned_response_id'],
                                                    $row['submitted_by'],
                                                    $row['submitted_on'],
                                                    $row['body'],
                                                    $row['body_format'],
                                                    $row['parent_id']);
        }
        return $this->latest_comment;
    }

    /**
     *
     * @param Tracker_Artifact_Changeset_Comment|-1 $comment
     */
    public function setLatestComment($comment) {
        $this->latest_comment = $comment;
    }

    /**
     * Return the ChangesetDao
     *
     * @return Tracker_Artifact_ChangesetDao The Dao
     */
    protected function getChangesetDao() {
        return new Tracker_Artifact_ChangesetDao();
    }

    /**
     * Returns the comment dao
     *
     * @return Tracker_Artifact_ChangesetCommentDao The dao
     */
    protected function getCommentDao() {
        return new Tracker_Artifact_Changeset_CommentDao();
    }

    /**
     * Returns true if there are changes in fields_data regarding this changeset, false if nothing has changed
     *
     * @param array $fields_data The data submitted (array of 'field_id' => 'value')
     *
     * @return boolean true if there are changes in fields_data regarding this changeset, false if nothing has changed
     */
    public function hasChanges($fields_data) {
        $has_changes = false;
        $used_fields = $this->getFormElementFactory()->getUsedFields($this->artifact->getTracker());
        reset($used_fields);
        while (!$has_changes && (list(,$field) = each($used_fields))) {
            if (!is_a($field, 'Tracker_FormElement_Field_ReadOnly')) {
               if (array_key_exists($field->id, $fields_data)) {
                   $current_value = $this->getValue($field);
                    if ($current_value) {
                        $has_changes = $field->hasChanges($this->getArtifact(), $current_value, $fields_data[$field->id]);
                    } else {
                        //There is no current value in the changeset for the submitted field
                        //It means that the field has been added afterwards.
                        //Then consider that there is at least one change (the value of the new field).
                        $has_changes = true;
                    }
                }
            }
        }
        return $has_changes;
    }

    /**
     * Return mail format diff between this changeset and previous one (HTML code)
     *
     * @return string The field difference between the previous changeset. or false if no changes
     */
    public function mailDiffToPrevious($format = 'html', $user = null, $ignore_perms = false) {
        return $this->diffToPrevious($format, $user, $ignore_perms, true);
    }

    /**
     * Return modal format diff between this changeset and previous one (HTML code)
     *
     * @return string The field difference between the previous changeset. or false if no changes
     */
    public function modalDiffToPrevious($format = 'html', $user = null, $ignore_perms = false) {
        return $this->diffToPrevious($format, $user, $ignore_perms, false, true);
    }

    /**
     * Return diff between this changeset and previous one (HTML code)
     *
     * @return string The field difference between the previous changeset. or false if no changes
     */
    public function diffToPrevious($format = 'html', $user = null, $ignore_perms = false, $for_mail = false, $for_modal = false) {
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

            if ( (! $ignore_perms && ! $field->userCanRead($user) ) || ! $current_changeset_value) {
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

                $diff = $current_changeset_value->mailDiff($previous_changeset_value, $format, $user, $artifact_id, $changeset_id);
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

    /**
    * Display diff messsage
    *
    * @param String $diff
    *
    */
    public function displayDiff($diff, $format, $field) {
        $result = false;
        switch($format) {
            case 'html':
                $result .= '<li>';
                $result .= '<span class="tracker_artifact_followup_changes_field"><b>'. $field->getLabel() .'</b></span> ';
                $result .= '<span class="tracker_artifact_followup_changes_changes">'. $diff .'</span>';
                $result .= '</li>';
            break;
            default://text
                $result .= ' * '.$field->getLabel().' : '.PHP_EOL;
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
    public function getUserManager() {
        return UserManager::instance();
    }

    public function getTracker() {
        return $this->artifact->getTracker();
    }

    /**
     * @protected for testing purpose
     */
    protected function getMailGatewayConfig() {
        return new MailGatewayConfig(
            new MailGatewayConfigDao()
        );
    }

    /**
     * @return ConfigNotificationAssignedTo
     */
    private function getTrackerConfigNotificationAssignedTo() {
        return new ConfigNotificationAssignedTo(
            new ConfigNotificationAssignedToDao()
        );
    }

    /**
     * @return bool
     */
    protected function isNotificationAssignedToEnabled() {
        $config_notification_assignedto = $this->getTrackerConfigNotificationAssignedTo();
        $project                        = $this->getTracker()->getProject();
        return $config_notification_assignedto->isAssignedToSubjectEnabled($project->getID());
    }

    /**
     * notify people
     *
     * @return void
     */
    public function notify() {
        $tracker = $this->getTracker();
        if ( ! $tracker->isNotificationStopped()) {
            $logger = $this->getLogger();
            $logger->debug('Start notification');

            $this->getArtifact()->forceFetchAllChangesets();

            // 0. Is update
            $is_update = ! $this->getArtifact()->isFirstChangeset($this);

            // 1. Get the recipients list
            $recipients = $this->getRecipients($is_update);
            $logger->debug('Recipients '.implode(', ', array_keys($recipients)));

            // 2. Compute the body of the message + headers
            $messages = array();

            $config = $this->getMailGatewayConfig();
            if ($config->isTokenBasedEmailgatewayEnabled() || $this->isNotificationAssignedToEnabled()) {
                $messages = $this->buildAMessagePerRecipient($recipients, $is_update);
            } else {
                $messages = $this->buildOneMessageForMultipleRecipients($recipients, $is_update);
            }

            // 3. Send the notification
            foreach ($messages as $message) {
                $logger->debug('Notify '.implode(', ', $message['recipients']));
                $this->sendNotification(
                    $message['recipients'],
                    $message['headers'],
                    $message['from'],
                    $message['subject'],
                    $message['htmlBody'],
                    $message['txtBody'],
                    $message['message-id']
                );
            }
            $logger->debug('End notification');
        }
    }

    protected function getLogger() {
        return new WrapperLogger(
            new TruncateLevelLogger(
                new BackendLogger(),
                ForgeConfig::get('sys_logger_level')
            ),
            'art #'.$this->getArtifact()->getId().' - cs #'.$this->getId()
        );
    }

    public function buildOneMessageForMultipleRecipients(array $recipients, $is_update) {
        $messages = array();
        foreach ($recipients as $recipient => $check_perms) {
            $user = $this->getUserFromRecipientName($recipient);

            if ($user) {
                $ignore_perms = !$check_perms;
                $recipient_mail = $user->getEmail();
                $message_content = $this->getMessageContent($user, $is_update, $check_perms);
                $headers = array_filter(array($this->getCustomReplyToHeader()));
                $hash = md5($message_content['htmlBody'] . $message_content['txtBody'] . serialize($message_content['subject']));

                if (isset($messages[$hash])) {
                    $messages[$hash]['recipients'][] = $recipient_mail;
                } else {
                    $messages[$hash] = $message_content;

                    $messages[$hash]['message-id'] = null;
                    $messages[$hash]['headers']    = $headers;
                    $messages[$hash]['recipients'] = array($recipient_mail);
                }

                $messages[$hash]['from'] = $this->getDefaultEmailSenderAddress();
            }
        }

        return $messages;
    }

    public function buildAMessagePerRecipient(array $recipients, $is_update) {
        $messages       = array();
        $anonymous_mail = 0;

        foreach ($recipients as $recipient => $check_perms) {
            $user = $this->getUserFromRecipientName($recipient);

            if (! $user->isAnonymous()) {
                $headers    = array_filter(array($this->getCustomReplyToHeader()));
                $message_id = $this->getMessageId($user);

                $messages[$message_id]               = $this->getMessageContent($user, $is_update, $check_perms);
                $messages[$message_id]['from']       = ForgeConfig::get('sys_name') . '<' .$this->getArtifact()->getTokenBasedEmailAddress() . '>';
                $messages[$message_id]['message-id'] = $message_id;
                $messages[$message_id]['headers']    = $headers;
                $messages[$message_id]['recipients'] = array($user->getEmail());

            } else {
                $headers = array($this->getAnonymousHeaders());

                $messages[$anonymous_mail]               = $this->getMessageContent($user, $is_update, $check_perms);
                $messages[$anonymous_mail]['from']       = $this->getDefaultEmailSenderAddress();
                $messages[$anonymous_mail]['message-id'] = null;
                $messages[$anonymous_mail]['headers']    = $headers;
                $messages[$anonymous_mail]['recipients'] = array($user->getEmail());

                $anonymous_mail += 1;
            }
        }

        return $messages;
    }

    /**
     * @return array
     */
    private function getAnonymousHeaders() {
        return array(
            "name" => "Reply-to",
            "value" => ForgeConfig::get('sys_noreply')
        );
    }

    private function getDefaultEmailSenderAddress() {
        $email_domain = ForgeConfig::get('sys_default_mail_domain');

        if (! $email_domain) {
            $email_domain = ForgeConfig::get('sys_default_domain');
        }

        return ForgeConfig::get('sys_name') . '<' . self::DEFAULT_MAIL_SENDER . '@' . $email_domain . '>';
    }

    private function getMessageId(PFUser $user) {
        $recipient_factory = $this->getRecipientFactory();
        $recipient         = $recipient_factory->getFromUserAndChangeset($user, $this);

        return $recipient->getEmail();
    }

    /**
     * @return Tracker_Artifact_MailGateway_RecipientFactory
     */
    protected function getRecipientFactory() {
        return Tracker_Artifact_MailGateway_RecipientFactory::build();
    }

    private function getCustomReplyToHeader() {
        $config         = $this->getMailGatewayConfig();
        $artifactbymail = new Tracker_ArtifactByEmailStatus($config);

        if ($config->isTokenBasedEmailgatewayEnabled()) {
            return array(
                "name" => "Reply-to",
                "value" => $this->getArtifact()->getTokenBasedEmailAddress()
            );
        } else if ($artifactbymail->canUpdateArtifactInInsecureMode($this->getArtifact()->getTracker())) {
            return array(
                "name" => "Reply-to",
                "value" => $this->getArtifact()->getInsecureEmailAddress()
            );
        }
    }

    private function getMessageContent($user, $is_update, $check_perms) {
        $ignore_perms = !$check_perms;

        $lang        = $user->getLanguage();

        $mailManager = new MailManager();
        $format      = $mailManager->getMailPreferencesByUser($user);


        $htmlBody = '';
        if ($format == Codendi_Mail_Interface::FORMAT_HTML) {
            $htmlBody .= $this->getBodyHtml($is_update, $user, $lang, $ignore_perms);
            $htmlBody .= $this->getHTMLAssignedToFilter($user);
        }

        $txtBody  = $this->getBodyText($is_update, $user, $lang, $ignore_perms);
        $txtBody .= $this->getTextAssignedToFilter($user);
        $subject  = $this->getSubject($user, $ignore_perms);

        $message = array();

        $message['htmlBody'] = $htmlBody;
        $message['txtBody']  = $txtBody;
        $message['subject']  = $subject;

        return $message;

    }

    protected function getUserFromRecipientName($recipient_name) {
        $um   = $this->getUserManager();
        $user = null;
        if ( strpos($recipient_name, '@') !== false ) {
            //check for registered
            $user = $um->getUserByEmail($recipient_name);

            //user does not exist (not registered/mailing list) then it is considered as an anonymous
            if ( ! $user ) {
                // don't call $um->getUserAnonymous() as it will always return the same instance
                // we don't want to override previous emails
                // So create new anonymous instance by hand
                $user = $um->getUserInstanceFromRow(
                    array(
                        'user_id' => 0,
                        'email'   => $recipient_name,
                    )
                );
            }
        } else {
            //is a login
            $user = $um->getUserByUserName($recipient_name);
        }

        return $user;
    }

    /**
     * Send a notification
     *
     * @param array  $recipients the list of recipients
     * @param array  $headers    the additional headers
     * @param string $from       the mail of the sender
     * @param string $subject    the subject of the message
     * @param string $htmlBody   the html content of the message
     * @param string $txtBody    the text content of the message
     * @param string $message_id the id of the message
     *
     * @return void
     */
    protected function sendNotification($recipients, $headers, $from, $subject, $htmlBody, $txtBody, $message_id) {
        $hp                = Codendi_HTMLPurifier::instance();
        $breadcrumbs       = array();
        $tracker           = $this->getTracker();
        $project           = $tracker->getProject();
        $artifactId        = $this->getArtifact()->getID();
        $project_unix_name = $project->getUnixName(true);
        $tracker_name      = $tracker->getItemName();
        $mail_enhancer     = new MailEnhancer();

        if($message_id) {
            $mail_enhancer->setMessageId($message_id);
        }

        $breadcrumbs[] = '<a href="'. get_server_url() .'/projects/'. $project_unix_name .'" />'. $project->getPublicName() .'</a>';
        $breadcrumbs[] = '<a href="'. get_server_url() .'/plugins/tracker/?tracker='. (int)$tracker->getId() .'" />'. $hp->purify($this->getTracker()->getName()) .'</a>';
        $breadcrumbs[] = '<a href="'. get_server_url().'/plugins/tracker/?aid='.(int)$artifactId.'" />'. $hp->purify($this->getTracker()->getName().' #'.$artifactId) .'</a>';

        $mail_enhancer->addPropertiesToLookAndFeel('breadcrumbs', $breadcrumbs);
        $mail_enhancer->addPropertiesToLookAndFeel('unsubscribe_link', $this->getUnsubscribeLink());
        $mail_enhancer->addPropertiesToLookAndFeel('title', $hp->purify($subject));
        $mail_enhancer->addHeader("X-Codendi-Project",     $project->getUnixName());
        $mail_enhancer->addHeader("X-Codendi-Tracker",     $tracker_name);
        $mail_enhancer->addHeader("X-Codendi-Artifact-ID", $this->artifact->getId());
        $mail_enhancer->addHeader('From', $from);

        foreach($headers as $header) {
            $mail_enhancer->addHeader($header['name'], $header['value']);
        }

        if ($htmlBody) {
            $htmlBody .= $this->getHTMLBodyFilter($project_unix_name, $tracker_name);
        }

        $txtBody .= $this->getTextBodyFilter($project_unix_name, $tracker_name);

        $mail_notification_builder = new MailNotificationBuilder(
            new MailBuilder(
                TemplateRendererFactory::build(),
                new MailFilter(UserManager::instance(), new URLVerification(), new MailLogger())
            )
        );
        $mail_notification_builder->buildAndSendEmail(
            $project,
            $recipients,
            $subject,
            $htmlBody,
            $txtBody,
            get_server_url().$this->getUri(),
            trackerPlugin::TRUNCATED_SERVICE_NAME,
            $mail_enhancer
        );
    }

    private function getTextBodyFilter($project_name, $tracker_name) {
        $project_filter = '=PROJECT='.$project_name;
        $tracker_filter = '=TRACKER='.$tracker_name;

        return PHP_EOL . $project_filter . PHP_EOL . $tracker_filter . PHP_EOL;
    }

    private function getHTMLBodyFilter($project_name, $tracker_name) {
        $filter  = '<div style="display: none !important;">';
        $filter .= '=PROJECT=' . $project_name . '<br>';
        $filter .= '=TRACKER=' . $tracker_name . '<br>';
        $filter .= '</div>';

        return $filter;
    }

    /**
     * @return string
     */
    private function getTextAssignedToFilter(PFUser $recipient) {
        $filter = '';

        if ($this->isNotificationAssignedToEnabled()) {
            $users = $this->getArtifact()->getAssignedTo($recipient);
            foreach ($users as $user) {
                $filter .= PHP_EOL . '=ASSIGNED_TO=' . $user->getUserName();
            }
            if ($filter !== '') {
                $filter .= PHP_EOL;
            }
        }

        return $filter;
    }

    /**
     * @return string
     */
    private function getHTMLAssignedToFilter(PFUser $recipient) {
        $filter = '';

        if ($this->isNotificationAssignedToEnabled()) {
            $filter = '<div style="display: none !important;">';
            $users = $this->getArtifact()->getAssignedTo($recipient);
            foreach ($users as $user) {
                $filter .= '=ASSIGNED_TO=' . $user->getUserName() . '<br>';
            }
            $filter .= '</div>';
        }

        return $filter;
    }

    public function removeRecipientsThatMayReceiveAnEmptyNotification(array &$recipients) {
        if ($this->getComment() && ! $this->getComment()->hasEmptyBody()) {
            return;
        }

        foreach ($recipients as $recipient => $check_perms) {
            if ( ! $check_perms) {
                continue;
            }

            $user = $this->getUserFromRecipientName($recipient);
            if ( ! $user || ! $this->userCanReadAtLeastOneChangedField($user)) {
                unset($recipients[$recipient]);
            }
        }
    }

    public function removeRecipientsThatHaveUnsubscribedArtifactNotification(array &$recipients) {
        $unsubscribers = $this->getArtifact()->getUnsubscribersIds();

        foreach ($recipients as $recipient => $check_perms) {
            $user = $this->getUserFromRecipientName($recipient);

            if (! $user || in_array($user->getId(), $unsubscribers)) {
                unset($recipients[$recipient]);
            }
        }
    }

    private function userCanReadAtLeastOneChangedField(PFUser $user) {
        $factory = $this->getFormElementFactory();

        foreach ($this->getValues() as $field_id => $current_changeset_value) {
            $field = $factory->getFieldById($field_id);
            $field_is_readable = $field && $field->userCanRead($user);
            $field_has_changed = $current_changeset_value && $current_changeset_value->hasChanged();
            if ($field_is_readable && $field_has_changed) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the recipients for notification
     *
     * @param bool $is_update It is an update, not a new artifact
     *
     * @return array of [$recipient => $checkPermissions] where $recipient is a usenrame or an email and $checkPermissions is bool.
     */
    public function getRecipients($is_update) {
        $factory = $this->getFormElementFactory();

        // 0 Is update
        $is_update = ! $this->getArtifact()->isFirstChangeset($this);

        // 1 Get from the fields
        $recipients = array();
        $this->forceFetchAllValues();
        foreach ($this->getValues() as $field_id => $current_changeset_value) {
            if ($field = $factory->getFieldById($field_id)) {
                if ($field->isNotificationsSupported() && $field->hasNotifications() && ($r = $field->getRecipients($current_changeset_value))) {
                    $recipients = array_merge($recipients, $r);
                }
            }
        }
        // 2 Get from the commentators
        $recipients = array_merge($recipients, $this->getArtifact()->getCommentators());
        $recipients = array_values(array_unique($recipients));


        //now force check perms for all this people
        $tablo = array();
        foreach($recipients as $r) {
            $tablo[$r] = true;
        }

        // 3 Get from the global notif
        foreach ($this->getArtifact()->getTracker()->getRecipients() as $r) {
            if ( $r['on_updates'] == 1 || !$is_update ) {
                foreach($r['recipients'] as $recipient) {
                    $tablo[$recipient] = $r['check_permissions'];
                }
            }
        }
        $this->removeRecipientsThatMayReceiveAnEmptyNotification($tablo);
        $this->removeRecipientsThatHaveUnsubscribedArtifactNotification($tablo);

        return $tablo;
    }

    /**
     * Get the text body for notification
     *
     * @param Boolean $is_update    It is an update, not a new artifact
     * @param String  $recipient    The recipient who will receive the notification
     * @param BaseLanguage $language The language of the message
     * @param Boolean $ignore_perms indicates if permissions have to be ignored
     *
     * @return String
     */
    public function getBodyText($is_update, $recipient_user, BaseLanguage $language, $ignore_perms) {
        $format = 'text';
        $art = $this->getArtifact();
        $um = $this->getUserManager();
        $user = $um->getUserById($this->submitted_by);

        $output = '+============== '.'['.$art->getTracker()->getItemName() .' #'. $art->getId().'] '.$art->fetchMailTitle($recipient_user, $format, $ignore_perms).' ==============+';
        $output .= PHP_EOL;
        $output .= PHP_EOL;
        $proto = ($GLOBALS['sys_force_ssl']) ? 'https' : 'http';
        $output .= ' <'. $proto .'://'. $GLOBALS['sys_default_domain'] .TRACKER_BASE_URL.'/?aid='. $art->getId() .'>';
        $output .= PHP_EOL;
        $output .= $language->getText('plugin_tracker_include_artifact', 'last_edited');
        $output .= ' '. $this->getUserHelper()->getDisplayNameFromUserId($this->submitted_by);
        $output .= ' on '.DateHelper::formatForLanguage($language, $this->submitted_on);
        if ( $comment = $this->getComment() ) {
            $output .= PHP_EOL;
            $output .= $comment->fetchMailFollowUp($format);
        }
        $output .= PHP_EOL;
        $output .= ' -------------- ' . $language->getText('plugin_tracker_artifact_changeset', 'header_changeset') . ' ---------------- ' ;
        $output .= PHP_EOL;
        $output .= $this->diffToPrevious($format, $recipient_user, $ignore_perms);
        $output .= PHP_EOL;
        $output .= ' -------------- ' . $language->getText('plugin_tracker_artifact_changeset', 'header_artifact') . ' ---------------- ';
        $output .= PHP_EOL;
        $output .= $art->fetchMail($recipient_user, $format, $ignore_perms);
        $output .= PHP_EOL;
        return $output;
    }
    /**
     * Get the html body for notification
     *
     * @param Boolean $is_update    It is an update, not a new artifact
     * @param String  $recipient    The recipient who will receive the notification
     * @param BaseLanguage $language The language of the message
     * @param Boolean $ignore_perms ???
     *
     * @return String
     */
    public function getBodyHtml($is_update, $recipient_user, BaseLanguage $language, $ignore_perms) {
        $format = 'html';
        $art = $this->getArtifact();
        $hp = Codendi_HTMLPurifier::instance();
        $followup = '';
        $changes = $this->mailDiffToPrevious($format, $recipient_user, $ignore_perms);
        // Display latest changes (diff)
        if ($comment = $this->getComment()) {
            $followup = $comment->fetchMailFollowUp($format);
        }

        $output =
        '<table style="width:100%">
            <tr>
                <td align="left" colspan="2">
                    <h1>'.$hp->purify($art->fetchMailTitle($recipient_user, $format, $ignore_perms)).'
                    </h1>
                </td>
            </tr>';

        if ($followup || $changes) {

            $output .=
                '<tr>
                    <td colspan="2" align="left">
                        <h2>'.$language->getText('plugin_tracker_artifact_changeset', 'header_html_changeset').'
                        </h2>
                    </td>
                </tr>';
            // Last comment
            if ($followup) {
                $output .= $followup;
            }
            // Last changes
            if ($changes) {
                //TODO check that the following is PHP compliant (what if I made a changes without a comment? -- comment is null)
                if (!empty($comment->body)) {
                    $output .= '
                        <tr>
                            <td colspan="2">
                                <hr size="1" />
                            </td>
                        </tr>';
                }
                $output .=
                    '<tr>
                        <td> </td>
                        <td align="left">
                            <ul>'.
                                $changes.'
                            </ul>
                        </td>
                    </tr>';
            }

            $artifact_link = get_server_url().'/plugins/tracker/?aid='.(int)$art->getId();

            $output .=
                '<tr>
                    <td> </td>
                    <td align="right">'.
                        $this->fetchHtmlAnswerButton($artifact_link).
                        '</span>
                    </td>
                </tr>';
        }
        $output .= '</table>';

        //Display of snapshot
        $snapshot = $art->fetchMail($recipient_user, $format, $ignore_perms);
        if ($snapshot) {
            $output .= $snapshot;
        }
        return $output;
    }

    /**
     * @return string html call to action button to include in an html mail
     */
    private function fetchHtmlAnswerButton($artifact_link) {
        return '<span class="cta">
            <a href="'. $artifact_link .'" target="_blank" rel="noreferrer">' .
                $GLOBALS['Language']->getText('tracker_include_artifact','mail_answer_now') .
            '</a>
        </span>';
    }

    /**
     * @return string html call to action button to include in an html mail
     */
    private function getUnsubscribeLink() {
        $link = get_server_url().'/plugins/tracker/?aid='.(int)$this->getArtifact()->getId().'&func=manage-subscription';

        return '<a href="'. $link .'" target="_blank" rel="noreferrer">' .
            $GLOBALS['Language']->getText('plugin_tracker_artifact','mail_unsubscribe') .
        '</a>';
    }

    /**
     * Wrapper for UserHelper
     *
     * @return UserHelper
     */
    protected function getUserHelper() {
        return UserHelper::instance();
    }

    /**
     * Get the subject for notification
     *
     * @return string
     */
    public function getSubject(PFUser $recipient, $ignore_perms=false) {
        $subject  = '['. $this->getArtifact()->getTracker()->getItemName() .' #'. $this->getArtifact()->getId() .'] ';
        $subject .= $this->getSubjectAssignedTo($recipient);
        $subject .= $this->getArtifact()->fetchMailTitle($recipient, 'text' ,$ignore_perms);
        return $subject;
    }

    /**
     * @return string
     */
    private function getSubjectAssignedTo(PFUser $recipient) {
        if ($this->isNotificationAssignedToEnabled()) {
            $users = $this->getArtifact()->getAssignedTo($recipient);
            if (in_array($recipient, $users, true)) {
                return '[Assigned to me] ';
            }
        }
        return '';
    }

    /**
     * Return the Tracker_Artifact of this changeset
     *
     * @return Tracker_Artifact The artifact of this changeset
     */
    function getArtifact() {
        return $this->artifact;
    }

    /**
     * Returns the Id of this changeset
     *
     * @return int The Id of this changeset
     */
    public function getId() {
        return $this->id;
    }

    public function exportCommentToSOAP() {
        $comment = $this->getComment();
        if ($comment) {
            $soap = $this->getSoapMetadata();
            return $comment->exportToSOAP($soap);
        }
    }

    private function getSoapMetadata() {
        $soap = array(
            'submitted_by' => $this->getSubmittedBy(),
            'email'        => $this->getEmailForUndefinedSubmitter(),
            'submitted_on' => $this->getSubmittedOn(),
        );
        return $soap;
    }

    public function getSoapValue(PFUser $user) {
        $soap    = $this->getSoapMetadata();
        $comment = $this->getComment();
        if (! $comment) {
            $comment = new Tracker_Artifact_Changeset_CommentNull($this);
        }
        $soap['last_comment'] = $comment->getSoapValue();
        $factory = $this->getFormElementFactory();
        foreach ($this->getValueDao()->searchById($this->id) as $row) {
            $field = $factory->getFieldById($row['field_id']);
            if ($field && $field->isCompatibleWithSoap()) {
                $soap['fields'][] = $field->getSoapValue($user, $this);
            }
        }
        return $soap;
    }

    public function getRESTValue(PFUser $user, $fields) {
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

    private function getRESTFieldValues(PFUser $user) {
        $values = array();
        $factory = $this->getFormElementFactory();

        foreach ($factory->getUsedFieldsForREST($this->getTracker()) as $field) {
            if ($field && $field->userCanRead($user)) {
                $values[] = $field->getRESTValue($user, $this);
            }
        }
        return array_filter($values);
    }

    private function getEmailForUndefinedSubmitter() {
        if (! $this->getSubmittedBy()) {
            return $this->getEmail();
        }
    }

    /**
     * Link to changeset in interface
     *
     * @return String
     */
    public function getUri() {
        return  TRACKER_BASE_URL.'/?aid='.$this->getArtifact()->getId().'#followup_'.$this->getId();
    }
}
