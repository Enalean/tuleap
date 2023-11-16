<?php
/**
 * Copyright (c) Enalean 2017 - Present. All rights reserved
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

use Tuleap\Date\DateHelper;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Tracker\Artifact\Artifact;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_DateReminderManager
{
    protected $tracker;

    /**
     * Constructor of the class
     *
     * @param Tracker $tracker Tracker associated to the manager
     *
     * @return Void
     */
    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * Obtain the tracker associated to the manager
     *
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * Process nightly job to send reminders
     */
    public function process(): void
    {
        $logger = BackendLogger::getDefaultLogger();
        if (! ($this->tracker->isNotificationStopped() || $this->tracker->isDeleted())) {
            $remiderFactory = $this->getDateReminderRenderer()->getDateReminderFactory();
            $reminders      = $remiderFactory->getTrackerReminders(false);
            foreach ($reminders as $reminder) {
                $logger->debug("[TDR] Processing reminder on " . $reminder->getField()->getName() . " (id: " . $reminder->getId() . ")");
                $artifacts = $this->getArtifactsByReminder($reminder);

                if (count($artifacts) == 0) {
                    $logger->debug("[TDR] No matching artifact.");
                }
                foreach ($artifacts as $artifact) {
                    if (! $reminder->mustNotifyClosedArtifacts() && ! $artifact->isOpen()) {
                        $logger->debug("[TDR] Artifact #" . $artifact->getId() . " matches but is not open. As per reminder configuration, skipping.");
                        continue;
                    }
                    $logger->debug("[TDR] Artifact #" . $artifact->getId() . " matches");
                    $this->sendReminderNotification($reminder, $artifact);
                }
            }
        } else {
            $logger->info("[TDR] Notifications are suspended");
        }
    }

    public function processReminderUpdate(HTTPRequest $request)
    {
        if (! $request->get('submit') && ! $request->get('confirm_delete')) {
            return;
        }
        try {
            switch ($request->get('action')) {
                case 'new_reminder':
                    $this->getDateReminderRenderer()->getDateReminderFactory()->addNewReminder($request);
                    $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-tracker', 'Date Reminder successfully added'));
                    break;
                case 'update_reminder':
                    $reminder = $this->getReminderFromRequestId($request->get('reminder_id'));
                    $this->getDateReminderRenderer()->getDateReminderFactory()->editTrackerReminder($reminder, $request);
                    $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-tracker', 'Date Reminder successfully updated'));
                    break;
                case 'confirm_delete_reminder':
                    $reminder = $this->getReminderFromRequestId($request->get('reminder_id'));
                    $this->getDateReminderRenderer()->getDateReminderFactory()->deleteTrackerReminder($reminder);
                    $GLOBALS['Response']->addFeedback(Feedback::INFO, dgettext('tuleap-tracker', 'Date Reminder successfully deleted'));
                    break;
            }
        } catch (Tracker_DateReminderException $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
        }
    }

    /**
     * @return Tracker_DateReminder
     * @throws Tracker_DateReminderException
     */
    private function getReminderFromRequestId($reminder_id)
    {
        $date_reminder_renderer = $this->getDateReminderRenderer();
        $reminder_factory       = $date_reminder_renderer->getDateReminderFactory();
        $reminder               = $reminder_factory->getReminder($reminder_id);
        if ($reminder === null) {
            throw new Tracker_DateReminderException(
                sprintf(
                    dgettext('tuleap-tracker', "Reminder with ID %d not found."),
                    $reminder_id,
                )
            );
        }
        $this->checkReminderMatchTracker($reminder);

        return $reminder;
    }

    /**
     * @throws Tracker_DateReminderException
     */
    private function checkReminderMatchTracker(?Tracker_DateReminder $reminder = null)
    {
        if ($reminder === null || $reminder->getTrackerId() !== $this->getTracker()->getId()) {
            $reminder_id = $reminder === null ? '' : $reminder->getId();
            throw new Tracker_DateReminderException(
                $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_reminder', [$reminder_id])
            );
        }
    }

    /**
     * Obtain date reminder renderer
     *
     * @return Tracker_DateReminderRenderer
     */
    public function getDateReminderRenderer()
    {
        return new Tracker_DateReminderRenderer($this->tracker);
    }

    /**
     * Send reminder
     *
     * @param Tracker_DateReminder $reminder Reminder that will send notifications
     * @param Artifact             $artifact Artifact for which reminders will be sent
     *
     * @return Void
     */
    protected function sendReminderNotification(Tracker_DateReminder $reminder, Artifact $artifact)
    {
        // 1. Get the recipients list
        $recipients = $reminder->getRecipients($artifact);

        // 2. Compute the body of the message + headers
        $messages = [];
        foreach ($recipients as $recipient) {
            if ($recipient && $artifact->userCanView($recipient) && $reminder->getField()->userCanRead($recipient)) {
                $this->buildMessage($reminder, $artifact, $messages, $recipient);
            }
        }

        // 3. Send the notification
        foreach ($messages as $m) {
            $historyDao = new ProjectHistoryDao();
            $historyDao->groupAddHistory("tracker_date_reminder_sent", $this->tracker->getName() . ":" . $reminder->getField()->getId(), $this->tracker->getGroupId(), $m['recipients']);
            $this->sendReminder($artifact, $m['recipients'], $m['headers'], $m['subject'], $m['htmlBody'], $m['txtBody']);
        }
    }

    /**
     * Build the reminder messages
     *
     * @param Tracker_DateReminder $reminder Reminder that will send notifications
     * @param Artifact             $artifact Artifact for which reminders will be sent
     * @param Array                $messages Messages
     * @param PFUser               $user     Receipient
     *
     * return Array
     */
    protected function buildMessage(Tracker_DateReminder $reminder, Artifact $artifact, &$messages, $user)
    {
        $mailManager = new MailManager();

        $recipient = $user->getEmail();
        $lang      = $user->getLanguage();
        $format    = $mailManager->getMailPreferencesByUser($user);

        //We send multipart mail: html & text body in case of preferences set to html
        $htmlBody = '';
        if ($format == Codendi_Mail_Interface::FORMAT_HTML) {
            $htmlBody .= $this->getBodyHtml($reminder, $artifact, $user, $lang);
        }
        $txtBody = $this->getBodyText($reminder, $artifact, $user, $lang);

        $subject = $this->getSubject($reminder, $artifact, $user);
        $headers = [];
        $hash    = md5($htmlBody . $txtBody . serialize($headers) . serialize($subject));
        if (isset($messages[$hash])) {
            $messages[$hash]['recipients'][] = $recipient;
        } else {
            $messages[$hash] = [
                'headers'    => $headers,
                'htmlBody'   => $htmlBody,
                'txtBody'    => $txtBody,
                'subject'    => $subject,
                'recipients' => [$recipient],
            ];
        }
    }

    /**
     * Send a notification
     *
     * @param Array  $recipients the list of recipients
     * @param Array  $headers    the additional headers
     * @param String $subject    the subject of the message
     * @param String $htmlBody   the html content of the message
     * @param String $txtBody    the text content of the message
     *
     * @return Void
     */
    protected function sendReminder(Artifact $artifact, $recipients, $headers, $subject, $htmlBody, $txtBody)
    {
        $hp            = Codendi_HTMLPurifier::instance();
        $breadcrumbs   = [];
        $project       = $this->getTracker()->getProject();
        $trackerId     = $this->getTracker()->getID();
        $artifactId    = $artifact->getID();
        $mail_enhancer = new MailEnhancer();

        $server_url = \Tuleap\ServerHostname::HTTPSUrl();

        $breadcrumbs[] = '<a href="' . $server_url . '/projects/' . $project->getUnixName(true) . '" />' . $hp->purify($project->getPublicName()) . '</a>';
        $breadcrumbs[] = '<a href="' . $server_url . '/plugins/tracker/?tracker=' . (int) $trackerId . '" />' . $hp->purify($this->getTracker()->getName()) . '</a>';
        $breadcrumbs[] = '<a href="' . $server_url . '/plugins/tracker/?aid=' . (int) $artifactId . '" />' . $hp->purify($this->getTracker()->getName() . ' #' . $artifactId) . '</a>';

        $mail_enhancer->addPropertiesToLookAndFeel('breadcrumbs', $breadcrumbs);
        $mail_enhancer->addPropertiesToLookAndFeel('title', $hp->purify($subject));
        $mail_enhancer->addHeader("X-Codendi-Project", $this->getTracker()->getProject()->getUnixName());
        $mail_enhancer->addHeader("X-Codendi-Tracker", $this->getTracker()->getItemName());
        $mail_enhancer->addHeader("X-Codendi-Artifact-ID", $artifact->getId());
        foreach ($headers as $header) {
            $mail_enhancer->addHeader($header['name'], $header['value']);
        }

        $mail_notification_builder = new MailNotificationBuilder(
            new MailBuilder(
                TemplateRendererFactory::build(),
                new MailFilter(
                    UserManager::instance(),
                    new ProjectAccessChecker(
                        new RestrictedUserCanAccessProjectVerifier(),
                        EventManager::instance()
                    ),
                    new MailLogger()
                )
            )
        );
        $mail_notification_builder->buildAndSendEmail(
            $project,
            $recipients,
            $subject,
            $htmlBody,
            $txtBody,
            $server_url . $artifact->getUri(),
            trackerPlugin::TRUNCATED_SERVICE_NAME,
            $mail_enhancer
        );
    }

    /**
     * Get the subject for reminder
     *
     * @param String $recipient The recipient who will receive the reminder
     *
     * @return String
     */
    public function getSubject($reminder, $artifact, $recipient)
    {
        $s = "[" . $this->tracker->getName() . "] " . sprintf(dgettext('tuleap-tracker', 'Reminder: \'%1$s\' %2$s for \'%3$s\''), $reminder->getField()->getLabel(), $reminder->getFieldValue($artifact), $artifact->getTitle());
        return $s;
    }

    /**
     * Get the text body for notification
     *
     * @param Tracker_DateReminder $reminder  Reminder that will send notifications
     * @param Artifact             $artifact  ???
     * @param PFUser               $recipient The recipient who will receive the notification
     * @param BaseLanguage         $language  The language of the message
     *
     * @return String
     */
    protected function getBodyText(Tracker_DateReminder $reminder, Artifact $artifact, $recipient, BaseLanguage $language)
    {
        $link = ' <' . \Tuleap\ServerHostname::HTTPSUrl() . TRACKER_BASE_URL . '/?aid=' . $artifact->getId() . '>';

        $output  = '+============== ' . '[' . $this->getTracker()->getItemName() . ' #' . $artifact->getId() . '] ' . $artifact->fetchMailTitle($recipient) . ' ==============+';
        $output .= PHP_EOL;

        $output .= sprintf(dgettext('tuleap-tracker', '%1$s was asked to remind you today that the \'%2$s\' in the artifact below is %3$s.'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME), $reminder->getField()->getLabel(), (string) $reminder->getFieldValue($artifact));
        $output .= PHP_EOL;
        $output .= sprintf(dgettext('tuleap-tracker', 'You can access the artifact here: %1$s'), $link);
        $output .= PHP_EOL;
        return $output;
    }

    /**
     * Get the html body for notification
     *
     * @param Tracker_DateReminder $reminder Reminder that will send notifications
     * @param PFUser  $recipient    The recipient who will receive the notification
     * @param BaseLanguage $language The language of the message
     *
     * @return String
     */
    protected function getBodyHtml(Tracker_DateReminder $reminder, Artifact $artifact, $recipient, BaseLanguage $language)
    {
        $format = Codendi_Mail_Interface::FORMAT_HTML;
        $hp     = Codendi_HTMLPurifier::instance();
        $link   = \Tuleap\ServerHostname::HTTPSUrl() . TRACKER_BASE_URL . '/?aid=' . $artifact->getId();

        $output  = '<h1>' . $hp->purify($artifact->fetchMailTitle($recipient, $format, false)) . '</h1>' . PHP_EOL;
        $output .= sprintf(dgettext('tuleap-tracker', '%1$s was asked to remind you today that the \'%2$s\' in the artifact below is %3$s.'), $hp->purify(ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)), $hp->purify($reminder->getField()->getLabel()), (string) $reminder->getFieldValue($artifact));
        $output .= '<br>';
        $output .= sprintf(dgettext('tuleap-tracker', 'You can access the artifact <a href="%1$s">here</a>.'), $link);
        $output .= '<br>';
        return $output;
    }

    /**
     * Get artifacts that will send notification for a reminder
     *
     * @param Tracker_DateReminder $reminder Reminder on which the notification is based on
     *
     * @return Array
     */
    public function getArtifactsByReminder(Tracker_DateReminder $reminder)
    {
        $time_string = '-';
        if ($reminder->getNotificationType() == 0) {
            $time_string = '+';
        }
        $time_string .= $reminder->getDistance() . ' days';
        $date         = DateHelper::getTimestampAtMidnight($time_string);
        $field        = $reminder->getField();
        return $field->getArtifactsByCriterias($date, $this->getTracker()->getId());
    }
}
