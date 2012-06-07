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
     * @param Codendi_Request $request      HTTP request
     * @param User            $current_user Current user
     *
     * @return ???
     */
    public function process(TrackerManager $engine, Codendi_Request $request, User $current_user) {
        return $this->sendReminderNotification();
    }
	
	/**
     * Send reminder 
     *
     *
     * @return Void
     */
    protected sendReminderNotification() {
	    $tracker = $this->getTracker();
		// 1. Get the recipients list
		$recipients = $this->getRecipients();
		
		// 2. Compute the body of the message + headers
		$messages = array();
		$um = $this->getUserManager();
		foreach ($recipients as $recipient) {
		    $user = null;
		    //is a login
		    $user = $um->getUserByUserName($recipient);
			if ($user) {
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
        //TODO
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
    public function getBodyText($recipient_user, BaseLanguage $language) {
        //TODO
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
    public function getBodyHtml($recipient_user, BaseLanguage $language) {
        //TODO
        return $output;
    }
	
	public function getArtifact(){
		//TODO
        return artifact;
    }
	
	/**
     * Retrive the recipient list given an ugroup_id 
     *
     *
     * @return Array
     */
	public function getRecipients(){
		//TODO
        return array();
    }
	
}

?>
