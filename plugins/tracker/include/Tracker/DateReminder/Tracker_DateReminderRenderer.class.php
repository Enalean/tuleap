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


class Tracker_DateReminderRenderer {

    protected $tracker;
    protected $dateReminderFactory;

    /**
     * Constructor of the class
     *
     * @param Tracker $tracker Tracker associated to the manager
     *
     * @return Void
     */
    public function __construct(Tracker $tracker) {
        $this->tracker             = $tracker;
        $this->dateReminderFactory = new Tracker_DateReminderFactory($this->tracker);
    }

    /**
     * Obtain the tracker associated to the Renderer
     *
     * @return Tracker
     */
    public function getTracker(){
        return $this->tracker;
    }

    /**
     * Obtain the Tracker_DateReminderFactory associated to the Renderer
     *
     * @return Tracker_DateReminderFactory
     */
    public function getDateReminderFactory(){
        return $this->dateReminderFactory;
    }

    /**
     * New date reminder form
     *
     * @return String
     */
    public function getNewDateReminderForm() {
        $output = '<form action="'.TRACKER_BASE_URL.'/?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;action=new_reminder" method="post" id="date_field_reminder_form">';
        $output .= '<input type="hidden" name="tracker_id" value="'.$this->tracker->id.'">';
        $output .= '<table border="0" width="700px"><tr height="30">';
        $output .= $this->dateReminderFactory->csrf->fetchHTMLInput();
        $output .= '<td><label>'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_send_to').':</label></td>
                        <td colspan=2><label>'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_notification_when').':</label></td>
                        <td><label>'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_field').':</label></td></tr>';
        $output .= '<tr valign="top"><td>'.$this->getUgroupsAllowedForTracker().'</td>';
        $output .= '<td> <input type="text" name="distance" size="3"> '.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_notification_distance_label').'</td>';
        $output .= '<td><select name="notif_type">
                        <option value="0"> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_before').'
                        <option value="1"> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_after').'
                    </select></td>';
        $output .= '<td>'.$this->getTrackerDateFields().'</td>';
        $output .= '</tr><tr height="35" valign="bottom"><td colspan=5><input type="submit" name="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','submit').'"></td>';
        $output .= '</tr></table></form>';
        return $output;
    }

    /**
     * Edit a given date reminder
     *
     * @param Integer $reminderId Id of the edited date reminder
     *
     * @return String
     */
    public function editDateReminder($reminderId) {
        $output   = '';
        $reminder = $this->dateReminderFactory->getReminder($reminderId);
        if ($reminder) {
            $notificationType = $reminder->getNotificationType();
            if ($notificationType == 1) {
                $after  = "selected";
                $before = "";
            } else {
                $after  = "";
                $before = "selected";
            }
            $reminderStatus = $reminder->getStatus();
            if ($reminderStatus == 1) {
                $enabled  = "selected";
                $disabled = "";
            } else {
                $enabled  = "";
                $disabled = "selected";
            }
            $output .= "<h3>".$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_edit_title')."</h3>";
            $output .= '<form action="'.TRACKER_BASE_URL.'/?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;action=update_reminder" method="post" name="update_date_field_reminder">';
            $output .= '<input type="hidden" name="reminder_id" value="'.$reminderId.'">
                        <input type="hidden" name="reminder_field_date" value="'.$reminder->getField()->getId().'">';
            $output .= '<table border="0" width="700px"><tr height="30">';
            $output .= $this->dateReminderFactory->csrf->fetchHTMLInput();
            $output .= '<td><label>'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_send_to').':</label></td>
                        <td colspan=2><label>'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_notification_when').':</label></td>
                        <td><label>'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_field').':</label></td>
                        <td><label>'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_status').':</label></td></tr>';
            $output .= '<tr valign="top"><td>'.$this->getUgroupsAllowedForTracker($reminderId).'</td>';
            $output .= '<td> <input type="text" name="distance" value="'.$reminder->getDistance().'" size="3"> '.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_notification_distance_label').'</td>';
            $output .= '<td><select name="notif_type">
                            <option value="0" '.$before.'> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_before').'
                            <option value="1" '.$after.'> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_after').'
                            </select></td>';
            $output .= '<td>'.$reminder->getField()->getLabel().'</td>';
            $output .= '<td><select name="notif_status">
                            <option value="0" '.$disabled.'> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_disabled').'
                            <option value="1" '.$enabled.'> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_enabled').'
                            </select></td>';
            $output .= '</tr><tr height="35" valign="bottom"><td colspan=5><input type="submit" name="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','submit').'"></td></tr>';
            $output .= '</table></form>';
        }
        return $output;
    }

    /**
     * Build a multi-select box of ugroup selectable to fill the new date field reminder.
     * It contains: all dynamic ugroups plus project members and admins.
     *
     * @params Integer $reminderId Id of the date reminder we want to customize its notified ugroups
     *
     * @return String
     */
    protected function getUgroupsAllowedForTracker($reminderId = Null) {
        $res = ugroup_db_get_existing_ugroups($this->tracker->group_id, array($GLOBALS['UGROUP_PROJECT_MEMBERS'],
                                                                              $GLOBALS['UGROUP_PROJECT_ADMIN']));
        $selectedUgroups = '';
        if (!empty($reminderId)) {
            $reminder        = $this->dateReminderFactory->getReminder($reminderId);
            $selectedUgroups = $reminder->getUgroups(true);
        }
        $output  = '<select name="reminder_ugroup[]" multiple>';
        while($row = db_fetch_array($res)) {
            if ($selectedUgroups && in_array($row['ugroup_id'], $selectedUgroups)) {
                $output .= '<option value="'.intval($row['ugroup_id']).'" selected>'.util_translate_name_ugroup($row['name']).'</option>';
            } else {
                $output .= '<option value="'.intval($row['ugroup_id']).'">'.util_translate_name_ugroup($row['name']).'</option>';
            }
        }
        $output  .= '</select>';
        return $output;
    }

    /**
     * Build a select box of all date fields used by a given tracker
     *
     * @return String
     */
    protected function getTrackerDateFields() {
        $tff               = Tracker_FormElementFactory::instance();
        $trackerDateFields = $tff->getUsedDateFields($this->tracker);
        $ouptut            = '<select name="reminder_field_date">';
        foreach ($trackerDateFields as $dateField) {
            $ouptut .= '<option value="'. $dateField->getId() .'">'.$dateField->getLabel().'</option>';
        }
        $ouptut .= '</select>';
        return $ouptut;
    }

    /**
     * Validate date field Id param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    public function validateFieldId(HTTPRequest $request) {
        $validFieldId = new Valid_UInt('reminder_field_date');
        $validFieldId->required();
        if ($request->valid($validFieldId)) {
            return $request->get('reminder_field_date');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_invalid_field', array($request->get('reminder_field_date')));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate distance param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    public function validateDistance(HTTPRequest $request) {
        $validDistance = new Valid_UInt('distance');
        $validDistance->required();
        if ($request->valid($validDistance)) {
            return $request->get('distance');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_invalid_distance', array($request->get('distance')));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate tracker id param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    public function validateTrackerId(HTTPRequest $request) {
        $validTrackerId = new Valid_UInt('tracker_id');
        $validTrackerId->required();
        if ($request->valid($validTrackerId)) {
            return $request->get('tracker_id');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_invalid_Tracker', array($request->get('tracker_id')));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate notification type param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    public function validateNotificationType(HTTPRequest $request) {
        $validNotificationType = new Valid_UInt('notif_type');
        $validNotificationType->required();
        if ($request->valid($validNotificationType)) {
            return $request->get('notif_type');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_invalid_notification_type', array($request->get('notif_type')));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate date reminder status.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    public function validateStatus(HTTPRequest $request) {
        $validStatus = new Valid_UInt('notif_status');
        $validStatus->required();
        if ($request->valid($validStatus)) {
            return $request->get('notif_status');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_invalid_status', array($request->get('notif_status')));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate date Reminder Id.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Integer
     */
    public function validateReminderId(HTTPRequest $request) {
        $validReminderId = new Valid_UInt('reminder_id');
        $validReminderId->required();
        if ($request->valid($validReminderId)) {
           return $request->get('reminder_id');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_invalid_reminder', array($request->get('reminder_id')));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate ugroup list param used for tracker reminder.
     * @TODO write less, write better
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Array
     */
    public function validateReminderUgroups(HTTPRequest $request) {
        $groupId = $this->getTracker()->getGroupId();
        $ugs       = ugroup_db_get_existing_ugroups($groupId, array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $GLOBALS['UGROUP_PROJECT_ADMIN']));
        $ugroupIds = array();
        while ($row = db_fetch_array($ugs)) {
            $ugroupIds[] = intval($row['ugroup_id']);
        }
        $validUgroupIds  = array();
        $selectedUgroups = $request->get('reminder_ugroup');
        if (!empty($selectedUgroups)) {
            foreach ($selectedUgroups as $ugroup) {
                if (in_array($ugroup, $ugroupIds)) {
                    $validUgroupIds[] = $ugroup;
                } else {
                    $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_invalid_ugroup', array($ugroup));
                    throw new Tracker_DateReminderException($errorMessage);
                }
            }
            if (!empty($validUgroupIds)) {
                return $validUgroupIds;
            }
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_empty_ugroup_param');
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Display all reminders of the tracker
     *
     * @return Void
     */
    public function displayAllReminders() {
        $titles           = array($GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_send_to'),
                                  $GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_notification_when'),
                                  $GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_field'),
                                  $GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_actions'));
        $i                = 0;
        $trackerReminders = $this->dateReminderFactory->getTrackerReminders(true);
        if (!empty($trackerReminders)) {
            $output = html_build_list_table_top($titles,false,false,false);
            foreach ($trackerReminders as $reminder) {
                if ($reminder->getStatus() == 1) {
                    $output .= '<tr class="'.util_get_alt_row_color($i++).'">';
                } else {
                    $output .= '<tr class="tracker_date_reminder">';
                }
                $output .= '<td>'.$reminder->getUgroupsLabel().'</td>';
                $output .= '<td>'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_notification_details', array($reminder->getDistance(), $reminder->getNotificationTypeLabel())).'</td>';
                $output .= '<td>'.$reminder->getField()->getLabel().'</td>';
                $output .= '<td><span style="float:left;"><a href="?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;reminder_id='. (int)$reminder->getId().'&amp;action=update_reminder" id="update_reminder"> '.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_update_action').' '. $GLOBALS['Response']->getimage('ic/edit.png') .'</a></span>';
                $output .= '&nbsp;&nbsp;&nbsp;<span style="float:right;"><a href="?func=admin-notifications&amp;tracker='.(int)$this->tracker->id.'&amp;action=delete_reminder&amp;reminder_id='.$reminder->getId().'" id="delete_reminder"> '.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_delete_action').' '. $GLOBALS['Response']->getimage('ic/bin.png') .'</a></span></td>';
                $output .= '</tr>';
            }
            $output .= '</table>';
            return $output;
        }
    }

    /**
     * Ask for confirmation before deleting a given date reminder
     *
     * @param Integer $reminderId Id of the date reminder to be deleted
     *
     * @return String
     */
    function displayConfirmDelete($reminderId) {
        $reminder        = $this->dateReminderFactory->getReminder($reminderId);
        $reminderString  = '<b>'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_send_to');
        $reminderString .= '&nbsp;'.$reminder->getUgroupsLabel().'&nbsp;';
        $reminderString .= $GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_notification_details', array($reminder->getDistance(), $reminder->getNotificationTypeLabel())).'&nbsp;"';
        $reminderString .= $reminder->getField()->getLabel().'"</b>';

        $output = '<p><form action="?func=admin-notifications&amp;tracker='.(int)$this->tracker->id.'" id="delete_reminder" method="POST" class="date_reminder_confirm_delete">';
        $output .= $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_adate_reminder_delete_txt', array($reminderString));
        $output .= '<div class="date_reminder_confirm_delete_buttons">';
        $output .= '<input type="hidden" name="action" value="confirm_delete_reminder" />';
        $output .= '<input type="hidden" name="tracker" value="'.(int)$this->tracker->id.'" />';
        $output .= '<input type="hidden" name="reminder_id" value="'.$reminderId.'" />';
        $output .= '<input type="submit" name="cancel_delete_reminder" value="'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_adate_reminder_delete_cancel').'" />';
        $output .= '<input type="submit" name="confirm_delete" value="'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_adate_reminder_delete_confirm').'" />';
        $output .= '</div>';
        $output .= '</form></p>';
        return $output;
    }

    /**
     * Display date reminders
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Void
     */
    public function displayDateReminders(HTTPRequest $request) {
        $output = '<h2>'.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_title').'</h2>';
        $output .= '<fieldset>';
        if ($request->get('action') == 'delete_reminder') {
           $output .= $this->displayConfirmDelete($request->get('reminder_id'));
        }
        $output .=$this->displayAllReminders();
        $output .= '<div id="tracker_reminder" style="display:none;"><p><label for="New Reminder">'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_add_title').'<input type="image" src="'.util_get_image_theme('ic/add.png').'" id="add_reminder" value="'.(int)$this->tracker->id.'"></label></div>';
        $output .= '<noscript>
        <p><a href="?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;action=add_reminder" id="add_reminder">'.$GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_add_title').'</a>
        </noscript>';
        if ($request->get('action') == 'add_reminder') {
            $output .= $this->getNewDateReminderForm();
        } elseif ($request->get('action') == 'update_reminder') {
           $output .= '<div id="update_reminder"></div>';
           $output .= $this->editDateReminder($request->get('reminder_id'));
        }
        $output .= '</fieldset>';
        echo $output;
    }

    /**
     * Display the footer
     *
     * @param TrackerManager $trackerManager Tracker manager
     *
     * @return String
     */
    public function displayFooter(TrackerManager $trackerManager) {
        return $this->tracker->displayFooter($trackerManager);
    }
}

?>