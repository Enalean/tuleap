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
require_once('dao/Tracker_DateReminder_Dao.class.php');
class Tracker_DateReminderManager {

    protected $tracker;

    /**
     * Constructor of the class
     *
     * @param Tracker $tracker Tracker associated to the manager
     *
     * @return Void
     */
    public function __construct($tracker) {
        $this->tracker = $tracker;
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
     * @param TrackerManager  $engine       Tracker manager
     *
     * @return Void
     */
    public function process(TrackerManager $engine) {
        return $this->sendReminderNotification();
    }

    /**
     * Send reminder 
     *
     *
     * @return Void
     */
    protected function sendReminderNotification() {
        $tracker = $this->getTracker();
        // 1. Get the recipients list
        $recipients = $this->getRecipients();

        // 2. Compute the body of the message + headers
        $messages = array();
        $um = $this->getUserManager();
        foreach ($recipients as $recipient) {
            $user = null;
            $user = $um->getUserByUserName($recipient);
            if ($user && $this->artifact->userCanView($user) && $this->reminder->dateField->userCanRead($user)) {
                $this->buildMessage($messages, $user);
            }
        }

        // 3. Send the notification
        foreach ($messages as $m) {
            $this->sendReminder(
            $m['recipients'],
            $m['headers'],
            $m['subject'],
            $m['htmlBody'],
            $m['txtBody']
            );
        }
    }

    /**
     * Build the reminder messages
     * 
     * @param  Array $messages
     * @param  User  $user
     * 
     * return Array
     *
     */
    protected function buildMessage(&$messages, $user) {
        $mailManager = new MailManager();
        
        $recipient = $user->getEmail();
        $lang      = $user->getLanguage();
        $format    = $mailManager->getMailPreferencesByUser($user);
        
        //We send multipart mail: html & text body in case of preferences set to html
        $htmlBody = '';
        if ($format == Codendi_Mail_Interface::FORMAT_HTML) {
            $htmlBody  .= $this->getBodyHtml($user, $lang);
        }
        $txtBody = $this->getBodyText($user, $lang);

        $subject   = $this->getSubject($user);
        $headers   = array(); 
        $hash = md5($htmlBody . $txtBody . serialize($headers) . serialize($subject));
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
     * @param array  $recipients the list of recipients
     * @param array  $headers    the additional headers
     * @param string $subject    the subject of the message
     * @param string $htmlBody   the html content of the message
     * @param string $txtBody    the text content of the message
     *
     * @return void
     */
    protected function sendReminder($recipients, $headers, $subject, $htmlBody, $txtBody) {
        $mail = new Codendi_Mail();
        $hp = Codendi_HTMLPurifier::instance();
        $breadcrumbs = array();
        $groupId = $this->getTracker()->getGroupId();
        $project = $this->getTracker()->getProject();
        $trackerId = $this->getTracker()->getID();
        $artifactId = $this->getArtifact()->getID();

        $breadcrumbs[] = '<a href="'. get_server_url() .'/projects/'. $project->getUnixName(true) .'" />'. $project->getPublicName() .'</a>';
        $breadcrumbs[] = '<a href="'. get_server_url() .'/plugins/tracker/?tracker='. (int)$trackerId .'" />'. $hp->purify(SimpleSanitizer::unsanitize($this->getTracker()->getName())) .'</a>';
        $breadcrumbs[] = '<a href="'. get_server_url().'/plugins/tracker/?aid='.(int)$artifactId.'" />'. $hp->purify($this->getTracker()->getName().' #'.$artifactId) .'</a>';

        $mail->getLookAndFeelTemplate()->set('breadcrumbs', $breadcrumbs);
        $mail->getLookAndFeelTemplate()->set('title', $hp->purify($subject));
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->addAdditionalHeader("X-Codendi-Project",     $this->getArtifact()->getTracker()->getProject()->getUnixName());
        $mail->addAdditionalHeader("X-Codendi-Tracker",     $this->getArtifact()->getTracker()->getItemName());
        $mail->addAdditionalHeader("X-Codendi-Artifact-ID", $this->getId());
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
     * @param string $recipient The recipient who will receive the reminder
     *
     * @return string
     */
    public function getSubject($recipient) {
        $s = "[" . $this->tracker->getTrackerName()."] ".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','subject', array($this->reminder->getLabel(),date("j F Y",$this->reminder->getDateValue()), $this->artifact->getSummary()));
        return $s;
    }
    
    /**
     * Get the text body for notification
     *
     * @param String  $recipient    The recipient who will receive the notification
     * @param BaseLanguage $language The language of the message
     *
     * @return String
     */
    protected function getBodyText($recipient, BaseLanguage $language) {
        $art = $this->getArtifact();
        $proto = ($GLOBALS['sys_force_ssl']) ? 'https' : 'http';
        $link .= ' <'. $proto .'://'. $GLOBALS['sys_default_domain'] .TRACKER_BASE_URL.'/?aid='. $art->getId() .'>';
        $week = date("W", $this->reminder->getDateValue());

        $output = '+============== '.'['.$this->getTracker()->getItemName() .' #'. $art->getId().'] '.$art->fetchMailTitle($recipient, $format, false).' ==============+';
        $output .= PHP_EOL;
    
        $output = "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_header',array('codex', $this->reminder->getLabel(),date("l j F Y",$this->reminder->getDateValue()), $week)).
            "\n\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_project',array($group->getPublicName())).
            "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_tracker',array($this->tracker->getTrackerName())).
            "\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_art',array($art->getSummary())).
            "\n".$this->reminder->getLabel().": ".date("D j F Y", $this->reminder->getDateValue()).
            "\n\n".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','body_art_link').
            "\n".$link."\n";
        return $output;
    }
    
    /**
     * Get the html body for notification
     *
     * @param String  $recipient    The recipient who will receive the reminder
     * @param BaseLanguage $language The language of the message
     *
     * @return String
     */
    protected function getBodyHtml($recipient_user, BaseLanguage $language) {
        //TODO
        return $output;
    }
    
    public function getArtifact(){
        //TODO
        return artifact;
    }
    
    /**
     * Retrieve the recipient list given an ugroup_id 
     *
     *
     * @return Array
     */
    public function getRecipients(){
        //TODO
        return array();
    }

    /**
     * New date reminder form
     *
     * @return String
     */
    public function getNewDateReminderForm() {
        $before = '';
        $after  = '';
        //@todo retrieve field
        //@todo Call dateReminder insertion method within a dedicated action (say insert_reminder) at Tracker_NotificationsManager::process() (around line 57)
        $output .= '<FORM ACTION="'.TRACKER_BASE_URL.'/?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;action=add_global" METHOD="POST" name="date_field_reminder_form">';
        //$out .='<INPUT TYPE="HIDDEN" NAME="field_id" VALUE="'.$this->reminder->dateField->getID().'">';
        $output .= '<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$this->tracker->group_id.'">
                    <INPUT TYPE="HIDDEN" NAME="tracker_id" VALUE="'.$this->tracker->id.'">';
        $output .= '<table border="0" width="900px"><TR height="30">';
        $output .= '<TD> <INPUT TYPE="TEXT" NAME="start" SIZE="3" VALUE="5"> day(s)</TD>';
        $output .= '<TD><SELECT NAME="notif_type">
                        <OPTION VALUE="0" '.$before.'> before
                        <OPTION VALUE="1" '.$after.'> after
                    </SELECT></TD>';
        $output .= '<TD><SELECT NAME="date_field">
                        <OPTION VALUE="0"> MyUgroup1
                        <OPTION VALUE="1"> MyUgroup2
                    </SELECT></TD>';
        $output .= '<TD>'.$this->getUgroupsAllowedForTracker().'</TD>';
        $output .= '<TD><INPUT type="submit" name="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','submit').'"></TD>';
        $output .= '</table></FORM>';
        return $output;
    }

    /**
     * Return a multi-select box of ugroup selectable to fill the new date field reminder.
     * It contains: all dynamic ugroups plus project members and admins.
     * @TODO check permissions on tracker, date field before display??
     *
     * @return String
     */
    protected function getUgroupsAllowedForTracker() {
        $res = ugroup_db_get_existing_ugroups($this->tracker->group_id, array($GLOBALS['UGROUP_PROJECT_MEMBERS'],
                                                                              $GLOBALS['UGROUP_PROJECT_ADMIN']));
        $output  = '<SELECT NAME="reminder_ugroup" multiple>';
        while($row = db_fetch_array($res)) {
            $output .= '<OPTION VALUE="'.$row['ugroup_id'].'">'.util_translate_name_ugroup($row['name']).'</OPTION>';
        }
        $output  .= '</SELECT>';
        return $output;
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
                                          $row['ugroup_id'],
                                          $row['notification_type'],
                                          $row['distance'],
                                          $row['status']);
    }

    /**
     * Get the Tracker_DateReminder dao
     *
     * @return Tracker_DateReminder_Dao
     */
    protected function getDao() {
        return new Tracker_DateReminder_Dao();
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
}

?>