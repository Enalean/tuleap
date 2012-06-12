<?php
/**
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

require_once('Tracker_DateReminder.class.php');
require_once('dao/Tracker_DateReminderDao.class.php');
require_once(dirname(__FILE__).'/../FormElement/Tracker_FormElementFactory.class.php');
require_once('common/mail/MailManager.class.php');
require_once 'common/date/DateHelper.class.php';
require_once('common/include/CSRFSynchronizerToken.class.php');

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
        $this->csrf    = new CSRFSynchronizerToken(TRACKER_BASE_URL.'/?func=admin-notifications&tracker='.$this->tracker->id.'&action=new_reminder');
    }

    /**
     * Obtain the tracker associated to the manager
     *
     * @return Tracker
     */
    public function getTracker(){
        return $this->tracker;
    }

    /**
     * Process nightly job to send reminders
     *
     * @return Void
     */
    public function process() {
        $reminders = $this->getTrackerReminders();
        foreach ($reminders as $reminder) {
            $artifacts = $this->getArtifactsByreminder($reminder);
            foreach ($artifacts as $artifact) {
                $this->sendReminderNotification($reminder, $artifact);
            }
        }
    }

    /**
     * Send reminder
     *
     * @param Tracker_DateReminder $reminder Reminder that will send notifications
     * @param Tracker_Artifact $artifact Artifact for which reminders will be sent
     *
     * @return Void
     */
    protected function sendReminderNotification(Tracker_DateReminder $reminder, Tracker_Artifact $artifact) {
        $tracker    = $this->getTracker();
        // 1. Get the recipients list
        $recipients = $reminder->getRecipients();

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
     * @param User             $user     Receipient
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
            //$htmlBody  .= $this->getBodyHtml($reminder, $user, $lang);
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
        $mail          = new Codendi_Mail();
        $hp            = Codendi_HTMLPurifier::instance();
        $breadcrumbs   = array();
        $groupId       = $this->getTracker()->getGroupId();
        $project       = $this->getTracker()->getProject();
        $trackerId     = $this->getTracker()->getID();
        $artifactId    = $artifact->getID();

        $breadcrumbs[] = '<a href="'. get_server_url() .'/projects/'. $project->getUnixName(true) .'" />'. $project->getPublicName() .'</a>';
        $breadcrumbs[] = '<a href="'. get_server_url() .'/plugins/tracker/?tracker='. (int)$trackerId .'" />'. $hp->purify(SimpleSanitizer::unsanitize($this->getTracker()->getName())) .'</a>';
        $breadcrumbs[] = '<a href="'. get_server_url().'/plugins/tracker/?aid='.(int)$artifactId.'" />'. $hp->purify($this->getTracker()->getName().' #'.$artifactId) .'</a>';

        $mail->getLookAndFeelTemplate()->set('breadcrumbs', $breadcrumbs);
        $mail->getLookAndFeelTemplate()->set('title', $hp->purify($subject));
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->addAdditionalHeader("X-Codendi-Project",     $this->getTracker()->getProject()->getUnixName());
        $mail->addAdditionalHeader("X-Codendi-Tracker",     $this->getTracker()->getItemName());
        $mail->addAdditionalHeader("X-Codendi-Artifact-ID", $artifact->getId());
        foreach($headers as $header) {
            $mail->addAdditionalHeader($header['name'], $header['value']);
        }
        $mail->setTo(implode(', ', $recipients));
        $mail->setSubject($subject);
        if ($htmlBody) {
            $mail->setBodyHTML($htmlBody);
        }
        $mail->setBodyText($txtBody);
        $mail->send();
    }

    /**
     * Get the subject for reminder
     *
     * @param String $recipient The recipient who will receive the reminder
     *
     * @return String
     */
    public function getSubject($reminder, $artifact, $recipient) {
        $s = "[" . $this->tracker->getName()."] ".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','subject', array($reminder->getField()->getLabel(),date("j F Y",$reminder->getField()->getValue()), $artifact->getTitle()));
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
        $format = Codendi_Mail_Interface::FORMAT_TEXT;
        $proto  = ($GLOBALS['sys_force_ssl']) ? 'https' : 'http';
        $link   = ' <'. $proto .'://'. $GLOBALS['sys_default_domain'] .TRACKER_BASE_URL.'/?aid='. $artifact->getId() .'>';
        // @TODO: add value_id param to Tracker_FormElement_Field::getValue()
        $week   = date("W", $reminder->getField()->getValue());

        $output = '+============== '.'['.$this->getTracker()->getItemName() .' #'. $artifact->getId().'] '.$artifact->fetchMailTitle($recipient, $format, false).' ==============+';
        $output .= PHP_EOL;
    
        $output = "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_header',array($GLOBALS['sys_name'], $reminder->getField()->getLabel(),date("l j F Y",$reminder->getField()->getValue()), $week)).
            "\n\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_project',array($this->getTracker()->getProject()->getPublicName())).
            "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_tracker',array($this->getTracker()->getName())).
            "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_art',array($artifact->getTitle())).
            "\n".$reminder->getField()->getLabel().": ".date("D j F Y", $reminder->getField()->getValue()).
            "\n\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_art_link').
            "\n".$link."\n";
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
        $format = Codendi_Mail_Interface::FORMAT_HTML;
        $proto  = ($GLOBALS['sys_force_ssl']) ? 'https' : 'http';
        $link   .= ' <'. $proto .'://'. $GLOBALS['sys_default_domain'] .TRACKER_BASE_URL.'/?aid='. $artifact->getId() .'>';
        $week   = date("W", $reminder->getField()->getValue());

       $output ='<h1>'.$hp->purify($art->fetchMailTitle($recipient, $format, false)).'</h1>'.PHP_EOL;

        $output = "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_header',array($GLOBALS['sys_name'], $reminder->getField()->getLabel(),date("l j F Y",$reminder->getField()->getValue()), $week)).
            "\n\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_project',array($this->getTracker()->getProject()->getPublicName())).
            "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_tracker',array($this->getTracker()->getName())).
            "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_art',array($artifact->getTitle())).
            "\n".$reminder->getField()->getLabel().": ".date("D j F Y", $reminder->getField()->getValue()).
            "\n\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_art_link').
            "\n".$link."\n";
        return $output;
    }

    /**
     * Retrieve all date reminders for a given tracker
     *
     * @return Array
     */
    public function getTrackerReminders() {
        $reminders          = array();
        $reminderManagerDao = $this->getDao();
        $dar = $reminderManagerDao->getDateReminders($this->tracker->getId());
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $reminders[] = $this->getInstanceFromRow($row);
            }
        }
        return $reminders;
    }

    /**
     * Validate date field Id param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    private function validateFieldId(HTTPRequest $request) {
        $validFieldId = new Valid_UInt('reminder_field_date');
        $validFieldId->required();
        $fieldId      = null;
        if ($request->valid($validFieldId)) {
            $fieldId = $request->get('reminder_field_date');
        }
        return $fieldId;
    }

    /**
     * Validate distance param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    private function validateDistance(HTTPRequest $request) {
        $validDistance = new Valid_UInt('distance');
        $validDistance->required();
        $distance      = null;
        if ($request->valid($validDistance)) {
            $distance = $request->get('distance');
        }
        return $distance;
    }

    /**
     * Validate tracker id param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    private function validateTrackerId(HTTPRequest $request) {
        $validTrackerId = new Valid_UInt('tracker_id');
        $validTrackerId->required();
        $trackerId      = null;
        if ($request->valid($validTrackerId)) {
            $trackerId = $request->get('tracker_id');
        }
        return $trackerId;
    }

    /**
     * Validate notification type param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    private function validateNotificationType(HTTPRequest $request) {
        $validNotificationType = new Valid_UInt('notif_type');
        $validNotificationType->required();
        $notificationType      = null;
        if ($request->valid($validNotificationType)) {
            $notificationType = $request->get('notif_type');
        }
        return $notificationType;
    }

    /**
     * Validate ugroup list param used for tracker reminder.
     * //TODO validate an array of ugroups Ids
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    private function validateReminderUgroups(HTTPRequest $request) {
        $validUgroupId = new Valid_WhiteList('reminder_ugroup');
        $validUgroupId->required();
        $ugroupId      = null;
        if ($request->valid($validUgroupId)) {
            $ugroupId = $request->get('reminder_ugroup');
        }
        return $ugroupId;
    }

    /**
     * Build a reminder instance
     *
     * @param array $row The data describing the reminder
     *
     * @return Tracker_DateReminder
     */
    public function getInstanceFromRow($row) {
        return new Tracker_DateReminder($row['reminder_id'],
                                          $row['tracker_id'],
                                          $row['field_id'],
                                          $row['ugroups'],
                                          $row['notification_type'],
                                          $row['distance'],
                                          $row['status']);
    }

    /**
     * Get the Tracker_DateReminder dao
     *
     * @return Tracker_DateReminderDao
     */
    protected function getDao() {
        return new Tracker_DateReminderDao();
    }

    /**
     * Get the reminder
     *
     * @param Integer  $reminderId    The reminder id
     *
     * @return Tracker_DateReminder
     */
    public function getReminder($reminderId) {
        if ($row = $this->getDao()->searchById($reminderId)->getRow()) {
            return $this->getInstanceFromRow($row);
        }
        return null;
    }

    /** Get artifacts that will send notification for a reminder
     *
     * @param Tracker_DateReminder $reminder Reminder on which the notification is based on
     *
     * @return Array
     */
    public function getArtifactsByreminder(Tracker_DateReminder $reminder) {
        $artifacts = array();
        $date      = DateHelper::getDistantDateFromToday($reminder->getDistance(), $reminder->getNotificationType());
        $field     = $reminder->getField();
        if ($field instanceof Tracker_FormElement_Field_LastUpdateDate) {
            // @TODO: Obtain artifacts of that tracker with changeset having that update date
        } elseif ($field instanceof Tracker_FormElement_Field_SubmittedOn) {
            // @TODO: Obtain artifacts of that tracker with changeset having that submitted date
        } elseif ($field instanceof Tracker_FormElement_Field_Date) {
            $dao = new Tracker_FormElement_Field_Value_DateDao();
            $dar = $dao->getArtifactsByFieldAndValue($reminder->getFieldId(), $date);
        }
        if ($dar && !$dar->isError()) {
            $artifactFactory = Tracker_ArtifactFactory::instance();
            foreach ($dar as $row) {
                $artifacts[] = $artifactFactory->getArtifactById($row['artifact_id']);
            }
        }
        return $artifacts;
    }

    /**
     * Display all reminders of the tracker
     *
     * @return Void
     */
    public function displayAllReminders() {
        $titles           = array('Reminder',
                                  $GLOBALS['Language']->getText('plugin_tracker_date_reminder','notification_status'),
                                  $GLOBALS['Language']->getText('plugin_tracker_date_reminder','notification_settings'),
                                  'Edit',
                                  $GLOBALS['Language']->getText('global', 'delete'));
        $i                = 0;
        $trackerReminders = $this->getTrackerReminders();
        print html_build_list_table_top($titles);
        foreach ($trackerReminders as $reminder) {
            print '<tr class="'.util_get_alt_row_color($i++).'">';
            print '<td>';
            print $reminder;
            print '</td>';
            print '<td>'.$reminder->getStatus().'</td>';
            print '<td>'.$reminder->getNotificationType().'</td>';
            print '<td><a href="?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;reminder_id='. (int)$reminder->reminderId.'&amp;action=update_reminder" id="update_reminder">'. $GLOBALS['Response']->getimage('ic/edit.png') .'</a>';
            print '<td><a href="?func=admin-notifications&amp;tracker='.(int)$this->tracker->id.'&amp;action=delete_reminder&amp;reminder_id='.$reminder->reminderId.'">'. $GLOBALS['Response']->getimage('ic/trash.png') .'</a></td>';
            print '</tr>';
        }
        print '</TABLE>';
    }

    /**
     * Get an instance of UserManager
     *
     * @return UserManager
     */
    public function getUserManager() {
        return UserManager::instance();
    }
    
}

?>