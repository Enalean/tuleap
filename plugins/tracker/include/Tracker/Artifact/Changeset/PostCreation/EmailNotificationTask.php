<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use ConfigNotificationAssignedTo;
use ForgeConfig;
use HTTPRequest;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_MailGateway_RecipientFactory;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSender;
use Tuleap\Tracker\Notifications\ConfigNotificationEmailCustomSenderFormatter;
use Tuleap\Tracker\Notifications\RecipientsManager;
use UserHelper;

final class EmailNotificationTask implements PostCreationTask
{
    public const DEFAULT_MAIL_SENDER           = 'forge__artifacts';
    public const DEFAULT_SENDER_EXPOSED_FIELDS = [
        'username' => 'user_name',
        'realname' => 'realname'
    ];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var UserHelper
     */
    private $user_helper;
    /**
     * @var RecipientsManager
     */
    private $recipients_manager;
    /**
     * @var MailGatewayConfig
     */
    private $mail_gateway_config;
    /**
     * @var MailSender
     */
    private $mail_sender;
    /**
     * @var ConfigNotificationAssignedTo
     */
    private $config_notification_assigned_to;
    /**
     * @var ConfigNotificationEmailCustomSender
     */
    private $config_notification_custom_sender;
    /**
     * @var Tracker_Artifact_MailGateway_RecipientFactory
     */
    private $recipient_factory;

    public function __construct(
        LoggerInterface $logger,
        UserHelper $user_helper,
        RecipientsManager $recipients_manager,
        Tracker_Artifact_MailGateway_RecipientFactory $recipient_factory,
        MailGatewayConfig $mail_gateway_config,
        MailSender $mail_sender,
        ConfigNotificationAssignedTo $config_notification_assigned_to,
        ConfigNotificationEmailCustomSender $config_notification_custom_sender
    ) {
        $this->logger                            = $logger;
        $this->user_helper                       = $user_helper;
        $this->recipients_manager                = $recipients_manager;
        $this->recipient_factory                 = $recipient_factory;
        $this->mail_gateway_config               = $mail_gateway_config;
        $this->mail_sender                       = $mail_sender;
        $this->config_notification_assigned_to   = $config_notification_assigned_to;
        $this->config_notification_custom_sender = $config_notification_custom_sender;
    }

    public function execute(Tracker_Artifact_Changeset $changeset)
    {
        $tracker = $changeset->getTracker();
        if ($tracker->isNotificationStopped()) {
            return;
        }
        $this->logger->debug('Start mail notification');

        // 0. Is update
        $is_update = ! $changeset->getArtifact()->isFirstChangeset($changeset);

        // 1. Get the recipients list
        $recipients = $this->recipients_manager->getRecipients($changeset, $is_update);
        $this->logger->debug('Recipients ' . implode(', ', array_keys($recipients)));

        // 2. Compute the body of the message + headers
        $messages = array();

        if ($this->mail_gateway_config->isTokenBasedEmailgatewayEnabled() || $this->isNotificationAssignedToEnabled($tracker)) {
            $messages = $this->buildAMessagePerRecipient($changeset, $recipients, $is_update);
        } else {
            $messages = $this->buildOneMessageForMultipleRecipients($changeset, $recipients, $is_update);
        }

        // 3. Send the notification
        foreach ($messages as $message) {
            $this->logger->debug('Notify ' . implode(', ', $message['recipients']));
            $this->mail_sender->send(
                $changeset,
                $message['recipients'],
                $message['headers'],
                $message['from'],
                $message['subject'],
                $message['htmlBody'],
                $message['txtBody'],
                $message['message-id']
            );
        }
        $this->logger->debug('End mail notification');
    }

    /**
     * @return bool
     */
    private function isNotificationAssignedToEnabled(\Tracker $tracker)
    {
        return $this->config_notification_assigned_to->isAssignedToSubjectEnabled($tracker);
    }

    public function buildAMessagePerRecipient(Tracker_Artifact_Changeset $changeset, array $recipients, $is_update)
    {
        $messages       = array();
        $anonymous_mail = 0;

        foreach ($recipients as $recipient => $check_perms) {
            $user = $this->recipients_manager->getUserFromRecipientName($recipient);

            if (! $user->isAnonymous()) {
                $headers    = array_filter(array($this->getCustomReplyToHeader($changeset->getArtifact())));
                $message_id = $this->getMessageId($user, $changeset);

                $messages[$message_id]               = $this->getMessageContent($changeset, $user, $is_update, $check_perms);
                $messages[$message_id]['from']       = $this->getSenderName($changeset) . '<' . $changeset->getArtifact()->getTokenBasedEmailAddress() . '>';
                $messages[$message_id]['message-id'] = $message_id;
                $messages[$message_id]['headers']    = $headers;
                $messages[$message_id]['recipients'] = array($user->getEmail());
            } else {
                $headers = array($this->getAnonymousHeaders());

                $messages[$anonymous_mail]               = $this->getMessageContent($changeset, $user, $is_update, $check_perms);
                $messages[$anonymous_mail]['from']       = $this->getEmailSenderAddress($changeset);
                $messages[$anonymous_mail]['message-id'] = null;
                $messages[$anonymous_mail]['headers']    = $headers;
                $messages[$anonymous_mail]['recipients'] = array($user->getEmail());

                $anonymous_mail += 1;
            }
        }

        return $messages;
    }

    public function buildOneMessageForMultipleRecipients(Tracker_Artifact_Changeset $changeset, array $recipients, $is_update)
    {
        $messages = array();
        foreach ($recipients as $recipient => $check_perms) {
            $user = $this->recipients_manager->getUserFromRecipientName($recipient);

            if ($user) {
                $ignore_perms    = !$check_perms;
                $recipient_mail  = $user->getEmail();
                $message_content = $this->getMessageContent($changeset, $user, $is_update, $check_perms);
                $headers         = array_filter(array($this->getCustomReplyToHeader($changeset->getArtifact())));
                $hash            = md5($message_content['htmlBody'] . $message_content['txtBody'] . serialize($message_content['subject']));

                if (isset($messages[$hash])) {
                    $messages[$hash]['recipients'][] = $recipient_mail;
                } else {
                    $messages[$hash] = $message_content;

                    $messages[$hash]['message-id'] = null;
                    $messages[$hash]['headers']    = $headers;
                    $messages[$hash]['recipients'] = array($recipient_mail);
                }

                $messages[$hash]['from'] = $this->getEmailSenderAddress($changeset);
            }
        }
        return $messages;
    }

    /**
     * @return array
     */
    private function getAnonymousHeaders()
    {
        return [
            'name'  => 'Reply-to',
            'value' => ForgeConfig::get('sys_noreply')
        ];
    }

    /**
     * @return string
     * */
    private function getEmailSenderAddress(Tracker_Artifact_Changeset $changeset)
    {
        $address = $this->getDefaultSenderAddress();
        $name = $this->getSenderName($changeset);
        return self::getEmailSender($name, $address);
    }

    /**
     * @param string $name the display name
     * @param string $address the email address
     * @return string
     * */
    private static function getEmailSender($name, $address)
    {
        return '"' . $name . '" <' . $address . '>';
    }

    /**
     * @return string
     * */
    private function getDefaultSenderAddress()
    {
        $email_domain = ForgeConfig::get('sys_default_mail_domain');
        if (! $email_domain) {
            $email_domain = ForgeConfig::get('sys_default_domain');
        }
        return self::DEFAULT_MAIL_SENDER . '@' . $email_domain;
    }

    /**
     * @return string
     * */
    private function getDefaultSenderName()
    {
        return ForgeConfig::get('sys_name');
    }

    /**
     * Looks for the custom sender setting and formats the name accordingly
     * @return string containing the formatted name if setting enabled
     * */
    private function getSenderName(Tracker_Artifact_Changeset $changeset)
    {
        $name = $this->getDefaultSenderName();
        if ($changeset) {
            $tracker             = $changeset->getTracker();
            $email_custom_sender = $this->config_notification_custom_sender->getCustomSender($tracker);
            if ($email_custom_sender['enabled']) {
                $name = $email_custom_sender['format'];
                $row  = $this->getAppropriateSenderFields($changeset);
                $cef  = new ConfigNotificationEmailCustomSenderFormatter($row);
                $name = $cef->formatString($name);
            }
        }
        return $name;
    }

    /**
     * Get the appropriate fields for putting into the email sender field
     * @return array of kv pairs with the applicable fields
     * */
    private function getAppropriateSenderFields(Tracker_Artifact_Changeset $changeset)
    {
        $fields = array();
        $user_row = $changeset->getSubmitter()->toRow();
        $user_fields = self::DEFAULT_SENDER_EXPOSED_FIELDS;
        foreach ($user_fields as $exposed_field => $internal_field) {
            $fields[$exposed_field] = $user_row[$internal_field];
        }
        return $fields;
    }

    private function getMessageId(\PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $recipient         = $this->recipient_factory->getFromUserAndChangeset($user, $changeset);
        return $recipient->getEmail();
    }

    private function getCustomReplyToHeader(\Tracker_Artifact $artifact)
    {
        $artifactbymail = new \Tracker_ArtifactByEmailStatus($this->mail_gateway_config);

        if ($this->mail_gateway_config->isTokenBasedEmailgatewayEnabled()) {
            return array(
                "name" => "Reply-to",
                "value" => $artifact->getTokenBasedEmailAddress()
            );
        } elseif ($artifactbymail->canUpdateArtifactInInsecureMode($artifact->getTracker())) {
            return array(
                "name" => "Reply-to",
                "value" => $artifact->getInsecureEmailAddress()
            );
        }
    }

    private function getMessageContent(Tracker_Artifact_Changeset $changeset, \PFUser $user, $is_update, $check_perms)
    {
        $ignore_perms = !$check_perms;

        $lang        = $user->getLanguage();

        $mailManager = new \MailManager();
        $format      = $mailManager->getMailPreferencesByUser($user);

        $htmlBody = '';
        if ($format == \Codendi_Mail_Interface::FORMAT_HTML) {
            $htmlBody .= $this->getBodyHtml($changeset, $is_update, $user, $lang, $ignore_perms);
            $htmlBody .= $this->getHTMLAssignedToFilter($changeset->getArtifact(), $user);
        }

        $txtBody  = $this->getBodyText($changeset, $is_update, $user, $lang, $ignore_perms);
        $txtBody .= $this->getTextAssignedToFilter($changeset->getArtifact(), $user);
        $subject  = $this->getSubject($changeset->getArtifact(), $user, $ignore_perms);

        $message = array();

        $message['htmlBody'] = $htmlBody;
        $message['txtBody']  = $txtBody;
        $message['subject']  = $subject;

        return $message;
    }

    /**
     * Get the text body for notification
     *
     * @param bool $is_update It is an update, not a new artifact
     * @param \PFUser  $recipient_user    The recipient who will receive the notification
     * @param \BaseLanguage $language The language of the message
     * @param bool $ignore_perms indicates if permissions have to be ignored
     *
     * @return String
     */
    public function getBodyText(Tracker_Artifact_Changeset $changeset, $is_update, $recipient_user, \BaseLanguage $language, $ignore_perms)
    {
        $format = 'text';
        $art    = $changeset->getArtifact();

        $output = '+============== ' . '[' . $art->getTracker()->getItemName() . ' #' . $art->getId() . '] ' . $art->fetchMailTitle($recipient_user, $format, $ignore_perms) . ' ==============+';
        $output .= PHP_EOL;
        $output .= PHP_EOL;
        $proto   = ForgeConfig::get('sys_https_host') ? 'https' : 'http';
        $output .= ' <' . $proto . '://' . ForgeConfig::get('sys_default_domain') . TRACKER_BASE_URL . '/?aid=' . $art->getId() . '>';
        $output .= PHP_EOL;
        $output .= $language->getText('plugin_tracker_include_artifact', 'last_edited');
        $output .= ' ' . $this->user_helper->getDisplayNameFromUserId($changeset->getSubmittedBy());
        $output .= ' on ' . \DateHelper::formatForLanguage($language, $changeset->getSubmittedOn());
        if ($comment = $changeset->getComment()) {
            $output .= PHP_EOL;
            $output .= $comment->fetchMailFollowUp($format);
        }
        $output .= PHP_EOL;
        $output .= ' -------------- ' . $language->getText('plugin_tracker_artifact_changeset', 'header_changeset') . ' ---------------- ';
        $output .= PHP_EOL;
        $output .= $changeset->diffToPrevious($format, $recipient_user, $ignore_perms);
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
     * @param bool $is_update It is an update, not a new artifact
     * @param \PFUser $recipient_user    The recipient who will receive the notification
     * @param \BaseLanguage $language The language of the message
     * @param bool $ignore_perms ???
     *
     * @return String
     */
    public function getBodyHtml(Tracker_Artifact_Changeset $changeset, $is_update, $recipient_user, \BaseLanguage $language, $ignore_perms)
    {
        $format   = 'html';
        $art      = $changeset->getArtifact();
        $hp       = \Codendi_HTMLPurifier::instance();
        $followup = '';
        $changes  = $changeset->mailDiffToPrevious($format, $recipient_user, $ignore_perms);
        // Display latest changes (diff)
        if ($comment = $changeset->getComment()) {
            $followup = $comment->fetchMailFollowUp($format);
        }

        $output =
            '<table style="width:100%">
            <tr>
                <td align="left" colspan="2">
                    <h1>' . $hp->purify($art->fetchMailTitle($recipient_user, $format, $ignore_perms)) . '
                    </h1>
                </td>
            </tr>';

        if ($followup || $changes) {
            $output .=
                '<tr>
                    <td colspan="2" align="left">
                        <h2>' . $language->getText('plugin_tracker_artifact_changeset', 'header_html_changeset') . '
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
                            <ul>' .
                    $changes . '
                            </ul>
                        </td>
                    </tr>';
            }

            $artifact_link = HTTPRequest::instance()->getServerUrl() . '/plugins/tracker/?aid=' . (int) $art->getId();

            $output .=
                '<tr>
                    <td> </td>
                    <td align="right">' .
                $this->fetchHtmlAnswerButton($artifact_link) .
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
    private function fetchHtmlAnswerButton($artifact_link)
    {
        return '<span class="cta">
            <a href="' . $artifact_link . '" target="_blank" rel="noreferrer">' .
            $GLOBALS['Language']->getText('tracker_include_artifact', 'mail_answer_now') .
            '</a>
        </span>';
    }

    /**
     * @return string
     */
    private function getTextAssignedToFilter(\Tracker_Artifact $artifact, \PFUser $recipient)
    {
        $filter = '';

        if ($this->isNotificationAssignedToEnabled($artifact->getTracker())) {
            $users = $artifact->getAssignedTo($recipient);
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
    private function getHTMLAssignedToFilter(\Tracker_Artifact $artifact, \PFUser $recipient)
    {
        $filter = '';

        if ($this->isNotificationAssignedToEnabled($artifact->getTracker())) {
            $filter = '<div style="display: none !important;">';
            $users = $artifact->getAssignedTo($recipient);
            foreach ($users as $user) {
                $filter .= '=ASSIGNED_TO=' . $user->getUserName() . '<br>';
            }
            $filter .= '</div>';
        }

        return $filter;
    }

    /**
     * Get the subject for notification
     *
     * @return string
     */
    private function getSubject(\Tracker_Artifact $artifact, \PFUser $recipient, $ignore_perms = false)
    {
        $subject  = '[' . $artifact->getTracker()->getItemName() . ' #' . $artifact->getId() . '] ';
        $subject .= $this->getSubjectAssignedTo($artifact, $recipient);
        $subject .= $artifact->fetchMailTitle($recipient, 'text', $ignore_perms);
        return $subject;
    }

    /**
     * @return string
     */
    private function getSubjectAssignedTo(\Tracker_Artifact $artifact, \PFUser $recipient)
    {
        if ($this->isNotificationAssignedToEnabled($artifact->getTracker())) {
            $assigned_to_users = $artifact->getAssignedTo($recipient);
            foreach ($assigned_to_users as $assigned_to_user) {
                if ((int) $assigned_to_user->getId() === (int) $recipient->getId()) {
                    return '[' . $recipient->getLanguage()->getText('plugin_tracker_include_type', 'assigned_to_me') . '] ';
                }
            }
        }
        return '';
    }
}
