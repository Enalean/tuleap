<?php
/* 
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('pre.php');
require_once('common/mail/Mail.class.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactFieldFactory.class.php');
require_once('common/tracker/Artifact.class.php');


//
// The artifact date reminder object
//
class ArtifactDateReminderFactory extends Error {
	
	// The notification id
	var $notification_id;
	
	// The reminder id
	var $reminder_id;
	
	// The tracker id
	var $group_artifact_id;
	
	// The artifact id
	var $artifact_id;
	
	// The field id
	var $field_id;
	
	// Number of notifications sent, for this event
	var $notification_sent;

	/**
	 *  Constructor.
	 *
	 */
	function ArtifactDateReminderFactory($notification_id) {
	    // Error constructor
	    $this->Error();
	    
	    // Set object attributes
	    $this->notification_id = $notification_id;  
	    $notif_array = $this->getNotificationData($notification_id);
	    $this->setFromArray($notif_array);
	    
	}	
	
	/**
	 *  Set the attributes values
	 *
	 * @param notif_array: the notification data array
	 *
	 * @return void
	 */
	function setFromArray($notif_array) {
	    
	    $this->reminder_id = $notif_array['reminder_id'];	    
	    $this->group_artifact_id = $notif_array['group_artifact_id'];
	    $this->artifact_id = $notif_array['artifact_id'];
	    $this->field_id = $notif_array['field_id'];
	    $this->notification_sent = $notif_array['notification_sent'];
	
	}
	
	/**
	* Get notification data from a given notification_id
	* 
	* @param notification_id: the notification id
	* 
	* @return array
	*/
	function getNotificationData($notification_id) {
	    
	    $qry = sprintf('SELECT * FROM artifact_date_reminder_processing'
			    .' WHERE notification_id=%d',
			    $notification_id);
	    $result = db_query($qry);
	    $rows = db_fetch_array($result);
	    
	    return $rows;   
	    
	}
	
	/**
	* Get the reminder_id attribute value
	* 
	* @return int
	*/
	function getReminderId() {
	    return $this->reminder_id;
	}
	
	/**
	* Get the group id
	* 
	* @return int
	*/
	function getGroupId() {
	    
	    $sql = sprintf('SELECT * FROM artifact_group_list'
			    .' WHERE group_artifact_id=%d',
			    $this->getGroupArtifactId());
	    $res = db_query($sql);
	    return db_result($res,0,'group_id');
	    
	}
	
	/**
	* Get the group_artifact_id attribute value
	* 
	* @return int
	*/
	function getGroupArtifactId() {
	    return $this->group_artifact_id;
	}
	
	/**
	* Get the artifact_id attribute value
	* 
	* @return int
	*/
	function getArtifactId() {
	    return $this->artifact_id;
	}
	
	/**
	* Get the field_id attribute value
	* 
	* @return int
	*/
	function getFieldId() {
	    return $this->field_id;
	}
	
	/**
	* Get the notification_sent attribute value
	* 
	* @return int
	*/
	function getNotificationSent() {
	    return $this->notification_sent;
	}
	
	/**
	*  Get the tracker name
	* 
	* @return string
	*/
	function getTrackerName() {
	
	    $group = group_get_object($this->getGroupId());   
	    $at = new ArtifactType($group,$this->getGroupArtifactId());
	    return $at->getName();
	
	}
	
	/**
	* Get the notification start date (Unix timestamp) for a given event 
	* 
	* @return int
	*/
	function getNotificationStartDate() {
	
	    $sql = sprintf('SELECT * FROM artifact_date_reminder_settings'
			    .' WHERE reminder_id=%d'
			    .' AND field_id=%d'
			    .' AND group_artifact_id=%d',
			    $this->getReminderId(),$this->getFieldId(),$this->getGroupArtifactId());
	    $res  =db_query($sql);
	    $start = db_result($res,0,'notification_start');    
	    $type = db_result($res,0,'notification_type');
	    $date_value = $this->getDateValue();    
	    $shift = intval($start * 24 * 3600);
	    if ($type == 0) {
	        //notification starts before occurence date
	        $notif_start_date = intval($date_value - $shift);
	    } else {
	        //notification starts after occurence date
	        $notif_start_date = intval($date_value + $shift);
	    }

	    return $notif_start_date;	    
	    
	}
	
	/**
	* Get the recurse of the event : how many reminders have to be sent
	*
	* @return int
	*/
	function getRecurse() {
	    
	    $sql = sprintf('SELECT * FROM artifact_date_reminder_settings'
			    .' WHERE reminder_id=%d'
			    .' AND field_id=%d'
			    .' AND group_artifact_id=%d',
			    $this->getReminderId(),$this->getFieldId(),$this->getGroupArtifactId());
	    $res = db_query($sql);
	    
	    return db_result($res,0,'recurse');
	    
	}

	/**
	* Get the frequency of the event : interval of reminder mails
	*
	* @return int
	*/
	function getFrequency() {
	    
	    $sql = sprintf('SELECT * FROM artifact_date_reminder_settings'
			    .' WHERE reminder_id=%d'
			    .' AND field_id=%d'
			    .' AND group_artifact_id=%d',
			    $this->getReminderId(),$this->getFieldId(),$this->getGroupArtifactId());
	    $res = db_query($sql);
	    
	    return db_result($res,0,'frequency');
	    
	}
	
	/**
	* Get the date (unix timestamp) of next reminder mail
	* 
	* @return int
	*/
	function getNextReminderDate() {
	
	    if ($this->getNotificationSent() < $this->getRecurse()) {
	        $shift = intval($this->getFrequency() * $this->getNotificationSent() * 24 * 3600);
	        return intval($this->getNotificationStartDate() + $shift);
	    }
	
	}
	
	/**
	* Get the date field value corresponding to this object
	* 
	* @return int
	*/
	function getDateValue() {
		    
	    $group = group_get_object($this->getGroupId());   
	    $at = new ArtifactType($group,$this->getGroupArtifactId());
	    $art_field_fact = new ArtifactFieldFactory($at);
	    $field = $art_field_fact->getFieldFromId($this->getFieldId());  	     

	    if (! $field->isStandardField()) {
	        $qry = sprintf('SELECT * FROM artifact_field_value'
				.' WHERE artifact_id=%d'
				.' AND field_id=%d',
				$this->getArtifactId(),$this->getFieldId());
	        $result = db_query($qry);
	        $valueDate = db_result($result,0,'valueDate');	    
	    } else {
	        //End Date
	        $qry = sprintf('SELECT * FROM artifact'
				.' WHERE artifact_id=%d'
				.' AND group_artifact_id=%d',
				$this->getArtifactId(),$this->getGroupArtifactId());
	        $result = db_query($qry);		    	    
	        $valueDate = db_result($result,0,'close_date');
	    }
	    
	    return $valueDate;
	    
	}
	
	/**
	* Get the list of users to be notified by the event
	* 
	* @return array
	*/
	function getNotifiedPeople() {
	
	    global $art_field_fact;
	    
	    //Instantiate a new Artifact object
	    $group = group_get_object($this->getGroupId());   
	    $at = new ArtifactType($group,$this->getGroupArtifactId());
	    $art_field_fact = new ArtifactFieldFactory($at);
	    $art = new Artifact($at,$this->getArtifactId());
	    
	    $notified_people = array();
	    $sql = sprintf('SELECT * FROM artifact_date_reminder_settings'
			    .' WHERE reminder_id=%d'
			    .' AND group_artifact_id=%d'
			    .' AND field_id=%d',
			    $this->getReminderId(),$this->getGroupArtifactId(),$this->getFieldId());
	    $res = db_query($sql);
	    $notif = db_result($res,0,'notified_people');
	    $notif_array = explode(",",$notif);
	    foreach ($notif_array as $item) {
	        switch ($item) {
		  case 1:
		    //Submitter
		    $submitter = $art->getSubmittedBy();
		    //add submitter in the 'notified_people' array
		    if (! in_array(user_getemail($submitter),$notified_people) && $submitter != 100 && $this->isUserAllowedToBeNotified($submitter)) {
		        $count = count($notified_people);
		        $notified_people[$count] = user_getemail($submitter);
		    }
		    break;
		  case 2:
		    //Assigned To
		    $assignee_array = array();
		    $multi_assigned_to = $art_field_fact->getFieldFromName('multi_assigned_to');
		    if (is_object($multi_assigned_to)) {
	                //Multi-Assigned To field
	                if ($multi_assigned_to->isUsed()) {
		             $assignee_array = $art->getMultiAssignedTo();
		        }		
	            } else {	                
	                $assigned_to = $art_field_fact->getFieldFromName('assigned_to');
			if (is_object($assigned_to)) {
			    //Assigned To field
			    if ($assigned_to->isUsed()) {
				$assignee_array = array($art->getValue('assigned_to'));
		            }
		        }
		    }	    		    
		    $index = count($notified_people);
		    if (count($assignee_array) > 0) {  
		        foreach ($assignee_array as $assignee) {
			    if (! in_array(user_getemail($assignee),$notified_people) && $assignee != 100 && $this->isUserAllowedToBeNotified($assignee)) {
			        $notified_people[$index] = user_getemail($assignee);
			        $index ++;
			    }
			}
		    }
		    break;
		  case 3:
		    //CC		      
		    $cc_array = $art->getCCIdList();
		    if (count($cc_array) > 0) {
		        $index = count($notified_people);
			foreach ($cc_array as $cc_id) {
			    $cc = user_getemail($cc_id);
			    if (! in_array($cc,$notified_people) && $this->isUserAllowedToBeNotified($cc_id)) {
			        //add CC list in the 'notified_people' array
			        $notified_people[$index] = $cc;
				$index++;
			    }
			}
		    }
		    break;
		  case 4:
		    //Commenter
		    $res_com = $art->getCommenters();
		    if (db_numrows($res_com) > 0) {
		        $index = count($notified_people);
		        while ($row = db_fetch_array($res_com)) {
			    $commenter = $row['mod_by'];
			    if (! in_array(user_getemail($commenter),$notified_people) && $commenter != 100 && $this->isUserAllowedToBeNotified($commenter)) {
			        //add Commenters in the 'notified_people' array
				$notified_people[$index] = user_getemail($commenter);
			        $index ++;
			    }			
			}
		    }
		    break;
		}
	    }
	    
	    return $notified_people;
	
	}
		
	/**
	* Increment the number of notifications sent
	* 
	*/
	function updateNotificationSent() {
	    
	    $upd = $this->getNotificationSent() + 1;
	    $sql = sprintf('UPDATE artifact_date_reminder_processing'
			    .' SET notification_sent=%d'
			    .' WHERE notification_id=%d',
			    $upd,$this->notification_id);
	    $res = db_query($sql);		    
	    
	    return $res;
	}
		
	/**
	* Check if user (user_id) is allowed to receive reminder mail
	*
	* @return boolean
	*/
	function isUserAllowedToBeNotified($user_id) {
	    
	    global $art_field_fact;
	    
	    $group = group_get_object($this->getGroupId());   
	    $at = new ArtifactType($group,$this->getGroupArtifactId());
	    $art_field_fact = new ArtifactFieldFactory($at);
	    $art = new Artifact($at,$this->getArtifactId());
	    $field = $art_field_fact->getFieldFromId($this->getFieldId());
	    
	    return ($art->userCanView($user_id) && $field->userCanRead($this->getGroupId(),$this->getGroupArtifactId(),$user_id));
	    
	}
	
	/**
	* Send the notification mail, about an event
	*
	* @return boolean
	*/
	function handleNotification() {
	    	    
	    global $art_field_fact;
	    
  	    $group = group_get_object($this->getGroupId());   
	    $at = new ArtifactType($group,$this->getGroupArtifactId());
	    $art_field_fact = new ArtifactFieldFactory($at);
	    $field =  $art_field_fact->getFieldFromId($this->getFieldId());
	    $art = new Artifact($at,$this->getArtifactId());
	    
	    $date_value = date("l, F jS Y",$this->getDateValue());
	    $week = date("W",$this->getDateValue());
	    $prj_name = util_get_group_name_from_id($this->getGroupId());
	    $tolist = implode($this->getNotifiedPeople(),',');
            $mail =& new Mail();
            $mail->setFrom($GLOBALS['sys_noreply']);	    
            $mail->setSubject("[" . $prj_name. " - '" . $this->getTrackerName()."' Tracker] ".$GLOBALS['Language']->getText('tracker_admin_index','reminder_mail_subject',array($art->getSummary())));
	    $mail->setBcc($tolist);
            $body = "\n".$GLOBALS['Language']->getText('tracker_admin_index','reminder_mail_header').
		    "\n\n".$GLOBALS['Language']->getText('tracker_admin_index','reminder_mail_body',array($field->getLabel(),$date_value,$week)).
		    "\n\n".$GLOBALS['Language']->getText('tracker_admin_index','reminder_mail_art_link').
		    "\n".get_server_url()."/tracker/?func=detail&aid=".$this->getArtifactId()."&atid=".$this->getGroupArtifactId()."&group_id=".$this->getGroupId().
		    "\n\n______________________________________________________________________".
		    "\n".$GLOBALS['Language']->getText('tracker_admin_index','reminder_mail_footer')."\n";
	    $mail->setBody($body);
            if ($mail->send()) {
	        return true;
	    } else {	        
	        return false;
	    }
	    
	}
	
	/**
	* compare current time/date with getNextReminder (are they in the same day ?)
	* if (ok) {
	*    (a)  get users to be notified
	*    (b)  send mail
	*    (c)  increment notifications sent
	* }
	*/
	function checkReminderStatus() {
	
	    if ($this->getNotificationSent() < $this->getRecurse()) {		
		$current_time = time();
		$next_day = intval($this->getNextReminderDate() + 24 * 3600);
		if ($current_time >= $this->getNextReminderDate() && $current_time < $next_day) {
		    if ($this->handleNotification()) {
		        //Increment 'notification_sent' field
			$this->updateNotificationSent();	
		    }
		}
	    }

	}

}

?>
