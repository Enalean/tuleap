<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
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

// The artifact date reminder object
class ArtifactDateReminderFactory
{

    // The notification id
    public $notification_id;

    // The reminder id
    public $reminder_id;

    // The tracker id
    public $group_artifact_id;

    // The artifact id
    public $artifact_id;

    // The field id
    public $field_id;

    /**
     * @var TrackerDateReminder_Logger
     */
    private $logger;

    public function __construct($notification_id, TrackerDateReminder_Logger $logger)
    {
        // Set object attributes
        $this->notification_id = $notification_id;
        $notif_array = $this->getNotificationData($notification_id);
        $this->setFromArray($notif_array);

        $this->logger = new TrackerDateReminder_Logger_Prefix($logger, '[Notif id ' . $notification_id . ']');
    }

    /**
     *  Set the attributes values
     *
     * @param notif_array: the notification data array
     *
     * @return void
     */
    public function setFromArray($notif_array)
    {
        $this->reminder_id       = $notif_array['reminder_id'];
        $this->group_artifact_id = $notif_array['group_artifact_id'];
        $this->artifact_id       = $notif_array['artifact_id'];
        $this->field_id          = $notif_array['field_id'];
    }

    /**
     * Get notification data from a given notification_id
     *
     * @param notification_id: the notification id
     *
     * @return array
     */
    public function getNotificationData($notification_id)
    {
        $qry = sprintf(
            'SELECT * FROM artifact_date_reminder_processing'
            . ' WHERE notification_id=%d',
            db_ei($notification_id)
        );
        $result = db_query($qry);
        $rows = db_fetch_array($result);

        return $rows;
    }

    /**
     * Get the reminder_id attribute value
     *
     * @return int
     */
    public function getReminderId()
    {
        return $this->reminder_id;
    }

    /**
     * Get the group id
     *
     * @return int
     */
    public function getGroupId()
    {
        $sql = sprintf(
            'SELECT group_id FROM artifact_group_list'
            . ' WHERE group_artifact_id=%d',
            db_ei($this->getGroupArtifactId())
        );
        $res = db_query($sql);
        return db_result($res, 0, 'group_id');
    }

    /**
     * Get the group_artifact_id attribute value
     *
     * @return int
     */
    public function getGroupArtifactId()
    {
        return $this->group_artifact_id;
    }

    /**
     * Get the artifact_id attribute value
     *
     * @return int
     */
    public function getArtifactId()
    {
        return $this->artifact_id;
    }

    /**
     * Get the field_id attribute value
     *
     * @return int
     */
    public function getFieldId()
    {
        return $this->field_id;
    }

    /**
     * Get the notification_sent value
     *
     * @return int
     */
    public function getNotificationSent()
    {
        $sql = sprintf(
            'SELECT notification_sent FROM artifact_date_reminder_processing'
            . ' WHERE notification_id = %d',
            db_ei($this->notification_id)
        );
        $res = db_query($sql);
        return db_result($res, 0, 'notification_sent');
    }

    /**
     *  Get the tracker name
     *
     * @return string
     */
    public function getTrackerName()
    {
        $group = ProjectManager::instance()->getProject($this->getGroupId());
        $at = new ArtifactType($group, $this->getGroupArtifactId());
        return $at->getName();
    }

    /**
     * Get the notification start date (Unix timestamp) for a given event
     *
     * @return int
     */
    public function getNotificationStartDate()
    {
        $sql = sprintf(
            'SELECT notification_start, notification_type FROM artifact_date_reminder_settings'
            . ' WHERE reminder_id=%d'
            . ' AND field_id=%d'
            . ' AND group_artifact_id=%d',
            db_ei($this->getReminderId()),
            db_ei($this->getFieldId()),
            db_ei($this->getGroupArtifactId())
        );
        $res  = db_query($sql);
        $start = db_result($res, 0, 'notification_start');
        $type = db_result($res, 0, 'notification_type');
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
    public function getRecurse()
    {
        $sql = sprintf(
            'SELECT recurse FROM artifact_date_reminder_settings'
                      . ' WHERE reminder_id=%d'
                      . ' AND field_id=%d'
                      . ' AND group_artifact_id=%d',
            db_ei($this->getReminderId()),
            db_ei($this->getFieldId()),
            db_ei($this->getGroupArtifactId())
        );
        $res = db_query($sql);

        return db_result($res, 0, 'recurse');
    }

    /**
     * Get the frequency of the event : interval of reminder mails
     *
     * @return int
     */
    public function getFrequency()
    {
        $sql = sprintf(
            'SELECT frequency FROM artifact_date_reminder_settings'
                      . ' WHERE reminder_id=%d'
                      . ' AND field_id=%d'
                      . ' AND group_artifact_id=%d',
            db_ei($this->getReminderId()),
            db_ei($this->getFieldId()),
            db_ei($this->getGroupArtifactId())
        );
        $res = db_query($sql);

        return db_result($res, 0, 'frequency');
    }

    /**
     * Get the date (unix timestamp) of next reminder mail
     *
     * @return int
     */
    public function getNextReminderDate()
    {
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
    public function getDateValue()
    {
        $group = ProjectManager::instance()->getProject($this->getGroupId());
        $at = new ArtifactType($group, $this->getGroupArtifactId());
        $art_field_fact = new ArtifactFieldFactory($at);
        $field = $art_field_fact->getFieldFromId($this->getFieldId());

        if (! $field->isStandardField()) {
            $qry = sprintf(
                'SELECT valueDate FROM artifact_field_value'
                          . ' WHERE artifact_id=%d'
                          . ' AND field_id=%d',
                db_ei($this->getArtifactId()),
                db_ei($this->getFieldId())
            );
            $result = db_query($qry);
            $valueDate = db_result($result, 0, 'valueDate');
        } else {
            //End Date
            $qry = sprintf(
                'SELECT close_date FROM artifact'
                          . ' WHERE artifact_id=%d'
                          . ' AND group_artifact_id=%d',
                db_ei($this->getArtifactId()),
                db_ei($this->getGroupArtifactId())
            );
            $result = db_query($qry);
            $valueDate = db_result($result, 0, 'close_date');
        }

        return $valueDate;
    }

    /**
     * Get the number of mails that should have been sent, but
     * weren't sent due to different possible issues
     *
     * @param int $current_time Time when the computation should occur (appart for test, should be time())
     *
     * @return int
     */
    public function getNotificationToBeSent($current_time)
    {
        $start_date = $this->getNotificationStartDate();
        if ($current_time >= $start_date + 24 * 3600) {
            $delay = intval(($current_time - $start_date) / (24 * 3600));
            return floor($delay);
        } else {
            return 0;
        }
    }

    /**
     * Get the list of users to be notified by the event
     *
     * @return array
     */
    public function getNotifiedPeople()
    {
        global $art_field_fact;

        //Instantiate a new Artifact object
        $group = ProjectManager::instance()->getProject($this->getGroupId());
        $at = new ArtifactType($group, $this->getGroupArtifactId());
        $art_field_fact = new ArtifactFieldFactory($at);
        $art = new Artifact($at, $this->getArtifactId(), false);

        $notified_people = array();
        $sql = sprintf(
            'SELECT notified_people FROM artifact_date_reminder_settings'
                      . ' WHERE reminder_id=%d'
                      . ' AND group_artifact_id=%d'
                      . ' AND field_id=%d',
            db_ei($this->getReminderId()),
            db_ei($this->getGroupArtifactId()),
            db_ei($this->getFieldId())
        );
        $res = db_query($sql);
        $notif = db_result($res, 0, 'notified_people');
        $notif_array = explode(",", $notif);
        foreach ($notif_array as $item) {
            if ($item == 1) {
                //Submitter
                $submitter = $art->getSubmittedBy();
                //add submitter in the 'notified_people' array
                if (! in_array(user_getemail($submitter), $notified_people) && $submitter != 100 && $this->isUserAllowedToBeNotified($submitter)) {
                    $count = count($notified_people);
                    $notified_people[$count] = user_getemail($submitter);
                }
            } elseif ($item == 2) {
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
                        if (! in_array(user_getemail($assignee), $notified_people) && $assignee != 100 && $this->isUserAllowedToBeNotified($assignee)) {
                            $notified_people[$index] = user_getemail($assignee);
                            $index ++;
                        }
                    }
                }
            } elseif ($item == 3) {
                //CC
                $cc_array = $art->getCCIdList();
                if (count($cc_array) > 0) {
                    $index = count($notified_people);
                    foreach ($cc_array as $cc_id) {
                        $cc = user_getemail($cc_id);
                        if (! in_array($cc, $notified_people) && $this->isUserAllowedToBeNotified($cc_id)) {
                            //add CC list in the 'notified_people' array
                            $notified_people[$index] = $cc;
                            $index++;
                        }
                    }
                }
            } elseif ($item == 4) {
                //Commenter
                $res_com = $art->getCommenters();
                if (db_numrows($res_com) > 0) {
                    $index = count($notified_people);
                    while ($row = db_fetch_array($res_com)) {
                        $commenter = $row['mod_by'];
                        if (! in_array(user_getemail($commenter), $notified_people) && $commenter != 100 && $this->isUserAllowedToBeNotified($commenter)) {
                            //add Commenters in the 'notified_people' array
                            $notified_people[$index] = user_getemail($commenter);
                            $index ++;
                        }
                    }
                }
            } elseif (preg_match("/^g/", $item)) {
                // user-group
                $ugr_id = (int) (substr($item, 1));
                if ($ugr_id > 100) {
                    // user-defined ugroup
                    $qry = ugroup_db_get_members($ugr_id);
                    $result = db_query($qry);
                    if (db_numrows($result) > 0) {
                        $idx = count($notified_people);
                        while ($row = db_fetch_array($result)) {
                            $usr = $row['user_id'];
                            if (! in_array(user_getemail($usr), $notified_people) && $usr != 100 && $this->isUserAllowedToBeNotified($usr)) {
                                //add ugroup members in the 'notified_people' array
                                $notified_people[$idx] = user_getemail($usr);
                                $idx ++;
                            }
                        }
                    }
                } else {
                    // predefined ugroup
                    $qry = ugroup_db_get_dynamic_members($ugr_id, $this->getGroupArtifactId(), $this->getGroupId());
                    if ($qry === null) {
                        return $notified_people;
                    }
                    $result = db_query($qry);
                    if (db_numrows($result) > 0) {
                        $idx = count($notified_people);
                        while ($row = db_fetch_array($result)) {
                            $usr = $row['user_id'];
                            if (! in_array(user_getemail($usr), $notified_people) && $usr != 100 && $this->isUserAllowedToBeNotified($usr)) {
                                //add ugroup members in the 'notified_people' array
                                $notified_people[$idx] = user_getemail($usr);
                                $idx ++;
                            }
                        }
                    }
                }
            }
        }

        return $notified_people;
    }

    /**
     * Increment the number of notifications sent
     *
     */
    public function updateNotificationSent($adjust = 0)
    {
        if (!$adjust) {
            $upd = $this->getNotificationSent() + 1;
        } else {
            $upd = $adjust;
        }
        $sql = sprintf(
            'UPDATE artifact_date_reminder_processing'
                      . ' SET notification_sent=%d'
                      . ' WHERE notification_id=%d',
            db_ei($upd),
            db_ei($this->notification_id)
        );
        $res = db_query($sql);

        return $res;
    }

    /**
     * Check if user (user_id) is allowed to receive reminder mail
     *
     * @return bool
     */
    public function isUserAllowedToBeNotified($user_id)
    {
        global $art_field_fact;

        $group = ProjectManager::instance()->getProject($this->getGroupId());
        $at = new ArtifactType($group, $this->getGroupArtifactId());
        $art_field_fact = new ArtifactFieldFactory($at);
        $art = new Artifact($at, $this->getArtifactId(), false);
        $field = $art_field_fact->getFieldFromId($this->getFieldId());

        return ($art->userCanView($user_id) && $field->userCanRead($this->getGroupId(), $this->getGroupArtifactId(), $user_id));
    }

    /**
     * Send the notification mail, about an event
     *
     * @return bool
     */
    public function handleNotification()
    {
        global $art_field_fact;

        $logger = new TrackerDateReminder_Logger_Prefix($this->logger, '[handleNotification]');
        $logger->info("Start");

        $group          = ProjectManager::instance()->getProject($this->getGroupId());
        $at             = new ArtifactType($group, $this->getGroupArtifactId());
        $art_field_fact = new ArtifactFieldFactory($at);
        $field          = $art_field_fact->getFieldFromId($this->getFieldId());
        $art            = new Artifact($at, $this->getArtifactId(), false);

        $logger->info("tracker: " . $this->getGroupArtifactId());
        $logger->info("artifact: " . $this->getArtifactId());

        $sent = true;
        $week = date("W", $this->getDateValue());

        $mail = new Codendi_Mail();
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setSubject("[" . $this->getTrackerName() . "] " . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_mail_subject', array($field->getLabel(),date("j F Y", $this->getDateValue()),$art->getSummary())));

        $body = "\n" . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_mail_body_header', array($field->getLabel(),date("l j F Y", $this->getDateValue()),$week)) .
        "\n\n" . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_mail_body_project', array($group->getPublicName())) .
        "\n" . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_mail_body_tracker', array($this->getTrackerName())) .
        "\n" . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_mail_body_art', array($art->getSummary())) .
        "\n" . $field->getLabel() . ": " . date("D j F Y", $this->getDateValue()) .
        "\n\n" . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_mail_body_art_link') .
        "\n" . HTTPRequest::instance()->getServerUrl() . "/tracker/?func=detail&aid=" . $this->getArtifactId() . "&atid=" . $this->getGroupArtifactId() . "&group_id=" . $this->getGroupId() .
        "\n\n______________________________________________________________________" .
        "\n" . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'reminder_mail_footer') . "\n";
        $mail->setBodyText($body);

        $allNotified = $this->getNotifiedPeople();
        $logger->info("notify: " . implode(', ', $allNotified));
        foreach ($allNotified as $notified) {
            $mail->setTo($notified);
            if (!$mail->send()) {
                $logger->error("faild to notify $notified");
                $sent = false;
            }
        }

        $logger->info("End");

        return $sent;
    }

    /**
     * compare current time/date with getNextReminder (are they in the same day ?)
     * if (ok) {
     *    (a)  get users to be notified
     *    (b)  send mail
     *    (c)  increment notifications sent
     * }
     *
     * @param int $current_time Time when the reminder status should be checked (appart for test should be time())
     */
    public function checkReminderStatus($current_time)
    {
        $this->logger->info("Start");

        $notificationSent = $this->getNotificationSent();
        $recurse          = $this->getRecurse();
        $this->logger->info("notification_sent = $notificationSent");
        $this->logger->info("recurse           = $recurse");
        if ($notificationSent < $recurse) {
            $notificationToBeSent = $this->getNotificationToBeSent($current_time);
            $this->logger->info("notification_to_be_sent = $notificationToBeSent");
            if ($notificationToBeSent > 0 && $notificationToBeSent > $this->getNotificationSent()) {
                //previous notification mails were not sent (for different possible reasons: push to prod of the feature, mail server crash, bug, etc)
                //in this case, re-adjust 'notification_sent' field
                $this->updateNotificationSent($notificationToBeSent);
                $this->logger->warn("update notification sent");
            }

            $next_day = intval($this->getNextReminderDate() + 24 * 3600);
            if ($current_time >= $this->getNextReminderDate() && $current_time < $next_day) {
                if ($this->handleNotification()) {
                    //Increment 'notification_sent' field
                    $this->updateNotificationSent();
                }
            } else {
                $this->logger->info("out of notification period");
            }
        }
        $this->logger->info("End");
    }
}
