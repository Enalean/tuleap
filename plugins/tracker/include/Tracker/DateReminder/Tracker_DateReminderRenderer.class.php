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
require_once('Tracker_DateReminderFactory.class.php');
require_once(dirname(__FILE__).'/../FormElement/Tracker_FormElementFactory.class.php');

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
        $output = '<form action="'.TRACKER_BASE_URL.'/?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;action=new_reminder" method="POST" id="date_field_reminder_form">';
        $output .= '<input type="HIDDEN" name="tracker_id" value="'.$this->tracker->id.'">';
        $output .= '<table border="0" width="900px"><TR height="30">';
        $output .= $this->dateReminderFactory->csrf->fetchHTMLInput();
        $output .= '<TD> <input type="text" name="distance" size="3"> day(s)</TD>';
        $output .= '<TD><select name="notif_type">
                        <option value="0"> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_before').'
                        <option value="1"> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_after').'
                    </select></TD>';
        $output .= '<TD>'.$this->getTrackerDateFields().'</TD>';
        $output .= '<TD>'.$this->getUgroupsAllowedForTracker().'</TD>';
        $output .= '<TD><input type="submit" name="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','submit').'"></TD>';
        $output .= '</table></form>';
        return $output;
    }

    /**
     * Edit a given date reminder
     *
     *  @param Integer $reminderId Id of the edited date reminder
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
            $output .= "Update Reminder";
            $output .= '<FORM ACTION="'.TRACKER_BASE_URL.'/?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;action=update_reminder" METHOD="POST" name="update_date_field_reminder">';
            $output .= '<INPUT TYPE="HIDDEN" NAME="reminder_id" VALUE="'.$reminderId.'">
                        <INPUT TYPE="HIDDEN" NAME="reminder_field_date" VALUE="'.$reminder->getField()->getId().'">';
            $output .= '<table border="0" width="900px"><TR height="30">';
            $output .= $this->dateReminderFactory->csrf->fetchHTMLInput();
            $output .= '<TD> <INPUT TYPE="TEXT" NAME="distance" VALUE="'.$reminder->getDistance().'" SIZE="3"> day(s)</TD>';
            $output .= '<TD><SELECT NAME="notif_type">
                            <OPTION VALUE="0" '.$before.'> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_before').'
                            <OPTION VALUE="1" '.$after.'> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_after').'
                            </SELECT></TD>';
            $output .= '<TD>'.$reminder->getField()->name.'</TD>';
            $output .= '<TD>'.$this->getUgroupsAllowedForTracker($reminderId).'</TD>';
            $output .= '<TD><SELECT NAME="notif_status">
                            <OPTION VALUE="0" '.$disabled.'> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_disabled').'
                            <OPTION VALUE="1" '.$enabled.'> '.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_enabled').'
                            </SELECT></TD>';
            $output .= '<TD><INPUT type="submit" name="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','submit').'"></TD>';
            $output .= '</table></FORM>';
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
        if (!empty($reminderId)) {
            $reminder        = $this->dateReminderFactory->getReminder($reminderId);
            $selectedUgroups = $reminder->getUgroups(true);
        }
        $output  = '<SELECT NAME="reminder_ugroup[]" multiple>';
        while($row = db_fetch_array($res)) {
            if ($selectedUgroups && in_array($row['ugroup_id'], $selectedUgroups)) {
                $output .= '<OPTION VALUE="'.intval($row['ugroup_id']).'" selected>'.util_translate_name_ugroup($row['name']).'</OPTION>';
            } else {
                $output .= '<OPTION VALUE="'.intval($row['ugroup_id']).'">'.util_translate_name_ugroup($row['name']).'</OPTION>';
            }
        }
        $output  .= '</SELECT>';
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
        $validUgroupIds = array();
        foreach ($request->get('reminder_ugroup') as $ugroup) {
            if (in_array($ugroup, $ugroupIds)) {
                $validUgroupIds[] = $ugroup;
            } else {
                $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_invalid_ugroup', array($ugroup));
                throw new Tracker_DateReminderException($errorMessage);
            }
        }
        if(!empty($validUgroupIds)) {
            return $validUgroupIds;
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
        $titles           = array('Reminder',
                                  $GLOBALS['Language']->getText('plugin_tracker_date_reminder','notification_status'),
                                  $GLOBALS['Language']->getText('plugin_tracker_date_reminder','notification_settings'),
                                  'Edit');
        $i                = 0;
        $trackerReminders = $this->dateReminderFactory->getTrackerReminders();
        if (!empty($trackerReminders)) {
            print html_build_list_table_top($titles);
            foreach ($trackerReminders as $reminder) {
                print '<tr class="'.util_get_alt_row_color($i++).'">';
                print '<td>';
                print $reminder;
                print '</td>';
                print '<td>'.$reminder->getReminderStatusLabel().'</td>';
                print '<td>'.$reminder->getNotificationTypeLabel().'</td>';
                print '<td><a href="?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;reminder_id='. (int)$reminder->getId().'&amp;action=update_reminder" id="update_reminder">'. $GLOBALS['Response']->getimage('ic/edit.png') .'</a>';
                print '</tr>';
            }
            print '</TABLE>';
        }
    }

    /**
     * Display date reminders
     *
     * @return Void
     */
    public function displayDateReminders(HTTPRequest $request) {
        print '<h2>'.$GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_title').'</h2>';
        print '<fieldset>';
        $this->displayAllReminders();
        $output = '<div id="tracker_reminder"></div>';
        $output .= '
        <script type="text/javascript">
        var reminderHtml = \'<p><label for="New Reminder"> Add reminder<input type="image" src="'.util_get_image_theme('ic/add.png').'" id="add_reminder" value="'.(int)$this->tracker->id.'"></label>\';
        document.getElementById("tracker_reminder").innerHTML = reminderHtml; 
        </script>
        <noscript>
        <p><a href="?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;action=add_reminder" id="add_reminder"> Add reminder </a>
        </noscript>';
        if ($request->get('action') == 'add_reminder') {
            $output .= $this->getNewDateReminderForm();
        } elseif ($request->get('action') == 'update_reminder') {
           $output .= '<div id="update_reminder"></div>';
               /*$output .= "<script type=\"text/javascript\">
            document.observe('dom:loaded', function() {
                $('update_reminder').observe('click', function (evt) {
                    var reminderDiv = new Element('div');
                    reminderDiv.insert('".$this->editDateReminder($request->get('reminder_id'))."');
                    Element.insert($('update_reminder'), reminderDiv);
                    Event.stop(evt);
                    return false;
                });
            });
            </script>";*/
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