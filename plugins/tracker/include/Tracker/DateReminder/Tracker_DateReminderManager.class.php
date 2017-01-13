<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
 * Copyright (c) Enalean 2017. All rights reserved
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

require_once('common/mail/MailManager.class.php');
require_once 'common/date/DateHelper.class.php';

class Tracker_DateReminderManager {

    protected $tracker;

    /**
     * Constructor of the class
     *
     * @param Tracker $tracker Tracker associated to the manager
     *
     * @return Void
     */
    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
    }

    /**
     * Obtain the tracker associated to the manager
     *
     * @return Tracker
     */
    public function getTracker() {
        return $this->tracker;
    }

    /**
     * Process nightly job to send reminders
     *
     * @return Void
     */
    public function process() {
        $logger = new BackendLogger();
        if ($this->tracker->stop_notification==0) {
            $remiderFactory = $this->getDateReminderRenderer()->getDateReminderFactory();
            $reminders      = $remiderFactory->getTrackerReminders(false);
            foreach ($reminders as $reminder) {
                $logger->debug("[TDR] Processing reminder on ".$reminder->getField()->getName()." (id: ".$reminder->getId().")");
                $artifacts = $this->getArtifactsByreminder($reminder);

                if (count($artifacts) == 0) {
                    $logger->debug("[TDR] No artifact match");
                }
                foreach ($artifacts as $artifact) {
                    $logger->debug("[TDR] Artifact #".$artifact->getId()." match");
                    $this->sendReminderNotification($reminder, $artifact);
                }
            }
        }
        else {
            $logger->info("[TDR] Notifications are suspended");
        }
    }

    /**
     * Process date reminder requests
     *
     * @param TrackerManager $trackerManager
     * @param HTTPRequest    $request
     * @param PFUser           $currentUser
     *
     * @return Void
     */
    public function processReminder(TrackerManager $trackerManager, HTTPRequest $request, $currentUser) {
        $action      = $request->get('action');
        $do_redirect = false;
        $feedback    = false;
        try {
            if ($request->get('submit') && $action == 'new_reminder') {
                $this->getDateReminderRenderer()->getDateReminderFactory()->addNewReminder($request);
                $feedback    = 'tracker_date_reminder_added';
                $do_redirect = true;
            } elseif ($request->get('submit') && $action == 'update_reminder') {
                $this->getDateReminderRenderer()->getDateReminderFactory()->editTrackerReminder($request);
                $feedback    = 'tracker_date_reminder_updated';
                $do_redirect = true;
            } elseif ($request->get('confirm_delete') && $action == 'confirm_delete_reminder') {
                $this->getDateReminderRenderer()->getDateReminderFactory()->deleteTrackerReminder($request->get('reminder_id'));
                $feedback = 'tracker_date_reminder_deleted';
            }
            if ($feedback) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_date_reminder',$feedback));
            }
        } catch (Tracker_DateReminderException $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
        }
        if ($do_redirect || $request->get('cancel_delete_reminder')) {
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?func=admin-notifications&tracker='.$this->getTracker()->getId());
        }
    }

    /**
     * Obtain date reminder renderer
     *
     * @return Tracker_DateReminderRenderer
     */
    public function getDateReminderRenderer() {
        return new Tracker_DateReminderRenderer($this->tracker);
    }

    /**
     * Send reminder
     *
     * @param Tracker_DateReminder $reminder Reminder that will send notifications
     * @param Tracker_Artifact     $artifact Artifact for which reminders will be sent
     *
     * @return Void
     */
    protected function sendReminderNotification(Tracker_DateReminder $reminder, Tracker_Artifact $artifact) {
        $tracker    = $this->getTracker();

        // 1. Get the recipients list
        $recipients = $reminder->getRecipients($artifact);

        // 2. Compute the body of the message + headers
        $messages   = array();
        foreach ($recipients as $recipient) {
            if ($recipient && $artifact->userCanView($recipient) && $reminder->getField()->userCanRead($recipient)) {
                $this->buildMessage($reminder, $artifact, $messages, $recipient);
            }
        }

        // 3. Send the notification
        foreach ($messages as $m) {
            $historyDao = new ProjectHistoryDao(CodendiDataAccess::instance());
            $historyDao->groupAddHistory("tracker_date_reminder_sent", $this->tracker->getName().":".$reminder->getField()->getId(), $this->tracker->getGroupId(), $m['recipients']);
            $this->sendReminder($artifact, $m['recipients'], $m['headers'], $m['subject'], $m['htmlBody'], $m['txtBody']);
        }
    }
    /**
     * Build the reminder messages
     *
     * @param Tracker_DateReminder $reminder Reminder that will send notifications
     * @param Tracker_Artifact $artifact Artifact for which reminders will be sent
     * @param Array            $messages Messages
     * @param PFUser             $user     Receipient
     *
     * return Array
     */
    protected function buildMessage(Tracker_DateReminder $reminder, Tracker_Artifact $artifact, &$messages, $user) {
        $mailManager = new MailManager();

        $recipient = $user->getEmail();
        $lang      = $user->getLanguage();
        $format    = $mailManager->getMailPreferencesByUser($user);

        //We send multipart mail: html & text body in case of preferences set to html
        $htmlBody  = '';
        if ($format == Codendi_Mail_Interface::FORMAT_HTML) {
            $htmlBody  .= $this->getBodyHtml($reminder, $artifact, $user, $lang);
        }
        $txtBody   = $this->getBodyText($reminder, $artifact, $user, $lang);

        $subject   = $this->getSubject($reminder, $artifact, $user);
        $headers   = array();
        $hash      = md5($htmlBody . $txtBody . serialize($headers) . serialize($subject));
        if (isset($messages[$hash])) {
            $messages[$hash]['recipients'][] = $recipient;
        } else {
            $messages[$hash] = array(
                    'headers'    => $headers,
                    'htmlBody'   => $htmlBody,
                    'txtBody'    => $txtBody,
                    'subject'    => $subject,
                    'recipients' => array($recipient),
            );
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
    protected function sendReminder(Tracker_Artifact $artifact, $recipients, $headers, $subject, $htmlBody, $txtBody) {
        $hp             = Codendi_HTMLPurifier::instance();
        $breadcrumbs    = array();
        $project        = $this->getTracker()->getProject();
        $trackerId      = $this->getTracker()->getID();
        $artifactId     = $artifact->getID();
        $mail_enhancer  = new MailEnhancer();

        $breadcrumbs[] = '<a href="'. get_server_url() .'/projects/'. $project->getUnixName(true) .'" />'. $project->getPublicName() .'</a>';
        $breadcrumbs[] = '<a href="'. get_server_url() .'/plugins/tracker/?tracker='. (int)$trackerId .'" />'. $hp->purify($this->getTracker()->getName()) .'</a>';
        $breadcrumbs[] = '<a href="'. get_server_url().'/plugins/tracker/?aid='.(int)$artifactId.'" />'. $hp->purify($this->getTracker()->getName().' #'.$artifactId) .'</a>';

        $mail_enhancer->addPropertiesToLookAndFeel('breadcrumbs', $breadcrumbs);
        $mail_enhancer->addPropertiesToLookAndFeel('title', $hp->purify($subject));
        $mail_enhancer->addHeader("X-Codendi-Project",     $this->getTracker()->getProject()->getUnixName());
        $mail_enhancer->addHeader("X-Codendi-Tracker",     $this->getTracker()->getItemName());
        $mail_enhancer->addHeader("X-Codendi-Artifact-ID", $artifact->getId());
        foreach($headers as $header) {
            $mail_enhancer->addHeader($header['name'], $header['value']);
        }

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
            get_server_url() . $artifact->getUri(),
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
    public function getSubject($reminder, $artifact, $recipient) {
        $s = "[" . $this->tracker->getName()."] ".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','subject', array($reminder->getField()->getLabel(), $reminder->getFieldValue($artifact), $artifact->getTitle()));
        return $s;
    }

    /**
     * Get the text body for notification
     *
     * @param Tracker_DateReminder $reminder     Reminder that will send notifications
     * @param Tracker_Artifact     $artifact     ???
     * @param String               $recipient    The recipient who will receive the notification
     * @param BaseLanguage         $language     The language of the message
     *
     * @return String
     */
    protected function getBodyText(Tracker_DateReminder $reminder, Tracker_Artifact $artifact, $recipient, BaseLanguage $language) {
        $protocol = ($GLOBALS['sys_force_ssl']) ? 'https' : 'http';
        $link     = ' <'.$protocol.'://'. $GLOBALS['sys_default_domain'] .TRACKER_BASE_URL.'/?aid='. $artifact->getId() .'>';

        $output   = '+============== '.'['.$this->getTracker()->getItemName() .' #'. $artifact->getId().'] '.$artifact->fetchMailTitle($recipient).' ==============+';
        $output   .= PHP_EOL;

        $output   .= $language->getText('plugin_tracker_date_reminder','body_header',array($GLOBALS['sys_name'], $reminder->getField()->getLabel(), $reminder->getFieldValue($artifact)));
        $output   .= PHP_EOL;
        $output   .= $language->getText('plugin_tracker_date_reminder','body_art_link', array($link));
        $output   .= PHP_EOL;
        return $output;
    }

    /**
     * Get the html body for notification
     *
     * @param Tracker_DateReminder $reminder Reminder that will send notifications
     * @param Tracker_Artifact $artifact
     * @param String  $recipient    The recipient who will receive the notification
     * @param BaseLanguage $language The language of the message
     *
     * @return String
     */
    protected function getBodyHtml(Tracker_DateReminder $reminder, Tracker_Artifact $artifact, $recipient, BaseLanguage $language) {
        $format   = Codendi_Mail_Interface::FORMAT_HTML;
        $hp       = Codendi_HTMLPurifier::instance();
        $protocol = ($GLOBALS['sys_force_ssl']) ? 'https' : 'http';
        $link     = $protocol.'://'. $GLOBALS['sys_default_domain'] .TRACKER_BASE_URL.'/?aid='. $artifact->getId();

        $output   ='<h1>'.$hp->purify($artifact->fetchMailTitle($recipient, $format, false)).'</h1>'.PHP_EOL;
        $output   .= $language->getText(
            'plugin_tracker_date_reminder',
            'body_header',
            array(
                $hp->purify($GLOBALS['sys_name']),
                $hp->purify($reminder->getField()->getLabel()),
                $reminder->getFieldValue($artifact)
            )
        );
        $output   .= '<br>';
        $output   .= $language->getText('plugin_tracker_date_reminder','body_art_html_link', array($link));
        $output   .= '<br>';
        return $output;
    }

    /**
     * Get artifacts that will send notification for a reminder
     *
     * @param Tracker_DateReminder $reminder Reminder on which the notification is based on
     *
     * @return Array
     */
    public function getArtifactsByreminder(Tracker_DateReminder $reminder) {
        $time_string = '-';
        if ($reminder->getNotificationType() == 0) {
            $time_string = '+';
        }
        $time_string .= $reminder->getDistance().' days';
        $date  = DateHelper::getTimestampAtMidnight($time_string);
        $field = $reminder->getField();
        return $field->getArtifactsByCriterias($date, $this->getTracker()->getId());
    }
}

?>
