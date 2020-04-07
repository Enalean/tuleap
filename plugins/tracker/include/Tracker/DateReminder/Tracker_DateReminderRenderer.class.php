<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

class Tracker_DateReminderRenderer
{

    protected $tracker;
    protected $dateReminderFactory;

    /**
     * Constructor of the class
     *
     * @param Tracker $tracker Tracker associated to the manager
     *
     * @return Void
     */
    public function __construct(Tracker $tracker)
    {
        $this->tracker             = $tracker;
        $this->dateReminderFactory = new Tracker_DateReminderFactory($this->tracker, $this);
    }

    /**
     * Obtain the tracker associated to the Renderer
     *
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * Obtain the Tracker_DateReminderFactory associated to the Renderer
     *
     * @return Tracker_DateReminderFactory
     */
    public function getDateReminderFactory()
    {
        return $this->dateReminderFactory;
    }

    /**
     * New date reminder form
     *
     * @return String
     */
    public function getNewDateReminderForm(CSRFSynchronizerToken $csrf_token)
    {
        $output = '<form method="post" id="date_field_reminder_form" class="form-inline"> ';
        $output .= '<input type="hidden" name="action" value="new_reminder">';
        $output .= $csrf_token->fetchHTMLInput();
        $output .= '<table border="0" cellpadding="5"><tr>';
        $output .= '<td><label>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_send_to') . ':</label></td>';
        $output .= '<td colspan=3><label>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_notification_when') . ':</label></td>';
        $output .= '<td><label>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_field') . ':</label></td></tr>';
        $output .= '<tr valign="top"><td>' . $this->getAllowedNotifiedForTracker() . '</td>';
        $output .= '<td><input type="text" name="distance" size="3" width="40" /></td>';
        $output .= '<td style="padding-top: 7px;">' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_notification_distance_label') . '</td>';
        $output .= '<td><select name="notif_type">
                        <option value="0"> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_before') . '
                        <option value="1"> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_after') . '
                    </select></td>';
        $output .= '<td>' . $this->getTrackerDateFields() . '</td></tr>';
        $output .= '<tr><td colspan="6"><input type="submit" name="submit" value="' . $GLOBALS['Language']->getText('global', 'add') . '"></td></tr>';
        $output .= '</form>';
        return $output;
    }

    /**
     * Edit a given date reminder
     *
     * @param int $reminderId Id of the edited date reminder
     *
     * @return String
     */
    public function editDateReminder($reminderId, CSRFSynchronizerToken $csrf_token)
    {
        $output   = '';
        $reminder = $this->dateReminderFactory->getReminder($reminderId);
        if ($reminder) {
            $notificationType = $reminder->getNotificationType();
            if ($notificationType == Tracker_DateReminder::AFTER) {
                $after  = "selected";
                $before = "";
            } else {
                $after  = "";
                $before = "selected";
            }
            $reminderStatus = $reminder->getStatus();
            if ($reminderStatus == Tracker_DateReminder::ENABLED) {
                $enabled  = "selected";
                $disabled = "";
            } else {
                $enabled  = "";
                $disabled = "selected";
            }
            $purifier = Codendi_HTMLPurifier::instance();

            $output .= "<h3>" . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_edit_title') . "</h3>";
            $output .= '<form method="post" name="update_date_field_reminder" class="form-inline">';
            $output .= '<input type="hidden" name="action" value="update_reminder">';
            $output .= '<input type="hidden" name="reminder_id" value="' . $reminderId . '">
                        <input type="hidden" name="reminder_field_date" value="' . $reminder->getField()->getId() . '">';
            $output .= '<table border="0" cellpadding="5"><tr>';
            $output .= $csrf_token->fetchHTMLInput();
            $output .= '<td><label>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_send_to') . ':</label></td>
                        <td colspan=3><label>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_notification_when') . ':</label></td>
                        <td><label>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_field') . ':</label></td>
                        <td><label>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_status') . ':</label></td></tr>';
            $output .= '<tr valign="top"><td>' . $this->getAllowedNotifiedForTracker($reminderId) . '</td>';
            $output .= '<td><input type="text" name="distance" value="' . $reminder->getDistance() . '" size="3" style="width: auto"></td><td style="padding-top: 7px;">' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_notification_distance_label') . '</td>';
            $output .= '<td><select name="notif_type" class="input-small">
                            <option value="0" ' . $before . '> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_before') . '
                            <option value="1" ' . $after . '> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_after') . '
                            </select></td>';
            $output .= '<td style="white-space: nowrap; padding-top: 7px;">' . $purifier->purify($reminder->getField()->getLabel()) . '</td>';
            $output .= '<td><select name="notif_status" class="input-small">
                            <option value="0" ' . $disabled . '> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_disabled') . '
                            <option value="1" ' . $enabled . '> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_enabled') . '
                            </select></td>';
            $output .= '</tr><tr height="35" valign="bottom"><td colspan=6><input type="submit" name="submit" value="' . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'submit') . '"></td></tr>';
            $output .= '</table></form>';
        }
        return $output;
    }

    /**
     * Build a multi-select box of ugroups and roles selectable to fill the new date field reminder.
     * It contains: all dynamic ugroups plus project members and admins and the defined tracker roles
     *
     * @params Integer $reminderId Id of the date reminder we want to customize its notified
     *
     * @return String
     */
    protected function getAllowedNotifiedForTracker($reminderId = null)
    {
        /** @psalm-suppress DeprecatedFunction */
        $res = ugroup_db_get_existing_ugroups($this->tracker->group_id, array($GLOBALS['UGROUP_PROJECT_MEMBERS'],
                                                                              $GLOBALS['UGROUP_PROJECT_ADMIN']));
        $selectedUgroups = '';
        $ugroups         = array();
        $roles           = array();
        if (!empty($reminderId)) {
            $reminder = $this->dateReminderFactory->getReminder($reminderId);
            $ugroups  = $reminder->getUgroups(true);
            $roles    = $reminder->getRoles();
            if ($roles) {
                foreach ($roles as $role) {
                    $selected[] = $role->getIdentifier();
                }
            }
        }
        $output  = '<select name="reminder_notified[]" multiple size=7 >';
        $output  .= '<optgroup label="' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_optgroup_label_ugroup') . '" >';
        while ($row = db_fetch_array($res)) {
            if ($ugroups && in_array($row['ugroup_id'], $ugroups)) {
                $output .= '<option value="u_' . intval($row['ugroup_id']) . '" selected>' . util_translate_name_ugroup($row['name']) . '</option>';
            } else {
                $output .= '<option value="u_' . intval($row['ugroup_id']) . '">' . util_translate_name_ugroup($row['name']) . '</option>';
            }
        }
        $output  .= '</optgroup>';
         $output  .= '<optgroup label="' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_optgroup_label_role') . '">';
         $all_possible_roles = array(
            new Tracker_DateReminder_Role_Submitter(),
            new Tracker_DateReminder_Role_Assignee(),
            new Tracker_DateReminder_Role_Commenter()
         );
         $purifier = Codendi_HTMLPurifier::instance();
         foreach ($all_possible_roles as $role) {
             if ($roles && in_array($role, $roles)) {
                 $output .= '<option value="r_' . $purifier->purify($role->getIdentifier()) . '" selected>' .
                    $purifier->purify($role->getLabel()) . '</option>';
             } else {
                 $output .= '<option value="r_' . $purifier->purify($role->getIdentifier()) . '">' . $purifier->purify($role->getLabel()) . '</option>';
             }
         }
         $output  .= '</optgroup>';
         $output  .= '</select>';
         return $output;
    }

    /**
     * Build a select box of all date fields used by a given tracker
     *
     * @return String
     */
    protected function getTrackerDateFields()
    {
        $purifier          = Codendi_HTMLPurifier::instance();
        $tff               = Tracker_FormElementFactory::instance();
        $trackerDateFields = $tff->getUsedDateFields($this->tracker);
        $ouptut            = '<select name="reminder_field_date">';
        foreach ($trackerDateFields as $dateField) {
            $ouptut .= '<option value="' . $purifier->purify($dateField->getId()) . '">' . $purifier->purify($dateField->getLabel()) . '</option>';
        }
        $ouptut .= '</select>';
        return $ouptut;
    }

    /**
     * Validate date field Id param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return int
     */
    public function validateFieldId(HTTPRequest $request)
    {
        $validFieldId = new Valid_UInt('reminder_field_date');
        $validFieldId->required();
        if ($request->valid($validFieldId)) {
            return $request->get('reminder_field_date');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_field', array($request->get('reminder_field_date')));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate distance param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return int
     */
    public function validateDistance(HTTPRequest $request)
    {
        $validDistance = new Valid_UInt('distance');
        $validDistance->required();
        if ($request->valid($validDistance)) {
            return $request->get('distance');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_distance', array($request->get('distance')));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate notification type param used for tracker reminder.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return int
     */
    public function validateNotificationType(HTTPRequest $request)
    {
        $validNotificationType = new Valid_UInt('notif_type');
        $validNotificationType->required();
        if ($request->valid($validNotificationType)) {
            return $request->get('notif_type');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_notification_type', array($request->get('notif_type')));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate date reminder status.
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return int
     */
    public function validateStatus(HTTPRequest $request)
    {
        $validStatus = new Valid_UInt('notif_status');
        $validStatus->required();
        if ($request->valid($validStatus)) {
            return $request->get('notif_status');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_status', array($request->get('notif_status')));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate ugroup list param used for tracker reminder.
     * @TODO write less, write better
     *
     * @param Array $selectedUgroups Array of selected user group
     *
     * @return Array
     */
    public function validateReminderUgroups(array $selectedUgroups)
    {
        $groupId = $this->getTracker()->getGroupId();
        /** @psalm-suppress DeprecatedFunction */
        $ugs       = ugroup_db_get_existing_ugroups($groupId, array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $GLOBALS['UGROUP_PROJECT_ADMIN']));
        $ugroupIds = array();
        while ($row = db_fetch_array($ugs)) {
            $ugroupIds[] = intval($row['ugroup_id']);
        }
        $validUgroupIds  = array();
        if (!empty($selectedUgroups)) {
            foreach ($selectedUgroups as $ugroup) {
                if (in_array($ugroup, $ugroupIds)) {
                    $validUgroupIds[] = $ugroup;
                } else {
                    $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_ugroup', array($ugroup));
                    throw new Tracker_DateReminderException($errorMessage);
                }
            }
        }
        return $validUgroupIds;
    }

    /**
     * Validate roles list param used for tracker reminder.
     *
     * @param Array $selectedRoles Array of selected tracker roles
     *
     * @return Array
     */
    public function validateReminderRoles(array $selectedRoles)
    {
        $validRoles = array();
        $all_possible_roles = array(
            new Tracker_DateReminder_Role_Submitter(),
            new Tracker_DateReminder_Role_Assignee(),
            new Tracker_DateReminder_Role_Commenter()
        );
        foreach ($all_possible_roles as $possible_role) {
            $roles[] = $possible_role->getIdentifier();
        }
        foreach ($selectedRoles as $role) {
            if (in_array($role, $roles)) {
                $validRoles[] = $role;
            } else {
                    $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_role_param', array($ugroup));
                    throw new Tracker_DateReminderException($errorMessage);
            }
        }
        return $validRoles;
    }

    /**
     * Scind the notified people for tracker reminder into dedicated arrays.
     * At least one list should be not empty
     *
     * @param HTTPRequest $request HTTP request
     *
     * @return Array
     */
    public function scindReminderNotifiedPeople(HTTPRequest $request)
    {
        $vArray = new Valid_Array('reminder_notified');
        $notified = $roles = $ugroups = array();
        if ($request->valid($vArray)) {
            $people = $request->get('reminder_notified');
            if ($people) {
                foreach ($people as $value) {
                    if ($value[0] == "r") {
                        $roles[] = substr($value, 2);
                    } else {
                        $ugroups[] = substr($value, 2);
                    }
                }
            }
            if (!empty($ugroups) || !empty($roles)) {
                $notified[] = $ugroups;
                $notified[] = $roles;
                return $notified;
            }
        }
        $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_empty_people_param');
        throw new Tracker_DateReminderException($errorMessage);
    }

    /**
     * Display all reminders of the tracker
     *
     * @return Void
     */
    public function displayAllReminders()
    {
        $titles           = array($GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_send_to'),
                                  $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_notification_when'),
                                  $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_field'),
                                  $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_actions'));
        $i                = 0;
        $trackerReminders = $this->dateReminderFactory->getTrackerReminders(true);
        if (!empty($trackerReminders)) {
            $purifier = Codendi_HTMLPurifier::instance();
            $output   = '';
            foreach ($trackerReminders as $reminder) {
                if ($reminder->getStatus() == Tracker_DateReminder::ENABLED) {
                    $output .= '<tr class="' . util_get_alt_row_color($i++) . '">';
                } else {
                    $output .= '<tr class="tracker_date_reminder">';
                }
                $output .= '<td>' . $reminder->getUgroupsLabel();
                $output .= $reminder->getRolesLabel() . '</td>';
                $output .= '<td>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_notification_details', array($reminder->getDistance(), $reminder->getNotificationTypeLabel())) . '</td>';
                $output .= '<td>' . $purifier->purify($reminder->getField()->getLabel()) . '</td>';
                $output .= '<td><span style="float:left;"><a href="?reminder_id=' . (int) $reminder->getId() . '&amp;action=update_reminder" id="update_reminder"> ' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_update_action') . ' ' . $GLOBALS['Response']->getimage('ic/edit.png') . '</a></span>';
                $output .= '&nbsp;&nbsp;&nbsp;<span style="float:right;"><a href="?action=delete_reminder&amp;reminder_id=' . $reminder->getId() . '" id="delete_reminder"> ' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_delete_action') . ' ' . $GLOBALS['Response']->getimage('ic/bin.png') . '</a></span></td>';
                $output .= '</tr>';
            }
            $html_table = new HTML_Table_Bootstrap();
            return $html_table->
                    setColumnsTitle($titles)->
                    setBody($output)->
                    render();
        }
    }

    /**
     * Ask for confirmation before deleting a given date reminder
     *
     * @param int $reminderId Id of the date reminder to be deleted
     *
     * @return String
     */
    public function displayConfirmDelete($reminderId, CSRFSynchronizerToken $csrf_token)
    {
        $purifier        = Codendi_HTMLPurifier::instance();
        $reminder        = $this->dateReminderFactory->getReminder($reminderId);
        $reminderString  = '<b>' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_send_to');
        $reminderString .= '&nbsp;' . $reminder->getUgroupsLabel() . '&nbsp;';
        $reminderString .= $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_notification_details', array($reminder->getDistance(), $reminder->getNotificationTypeLabel())) . '&nbsp;"';
        $reminderString .= $purifier->purify($reminder->getField()->getLabel()) . '"</b>';

        $output = '<p><form id="delete_reminder" method="POST" class="date_reminder_confirm_delete">';
        $output .= $csrf_token->fetchHTMLInput();
        $output .= $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_adate_reminder_delete_txt', array($reminderString));
        $output .= '<div class="date_reminder_confirm_delete_buttons">';
        $output .= '<input type="hidden" name="action" value="confirm_delete_reminder" />';
        $output .= '<input type="hidden" name="tracker" value="' . (int) $this->tracker->id . '" />';
        $output .= '<input type="hidden" name="reminder_id" value="' . $reminderId . '" />';
        $output .= '<input type="submit" name="cancel_delete_reminder" value="' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_adate_reminder_delete_cancel') . '" />';
        $output .= '<input type="submit" name="confirm_delete" value="' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_adate_reminder_delete_confirm') . '" />';
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
    public function displayDateReminders(HTTPRequest $request, CSRFSynchronizerToken $csrf_token)
    {
        $output = '<h2 class="almost-tlp-title">' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_title') . '</h2>';
        $output .= '<fieldset>';
        if ($request->get('action') == 'delete_reminder') {
            $output .= $this->displayConfirmDelete($request->get('reminder_id'), $csrf_token);
        }
        $output .= $this->displayAllReminders();
        $output .= '<div id="tracker_reminder" style="display:none;"><p><label for="New Reminder">' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_add_title') . '<input type="image" src="' . util_get_image_theme('ic/add.png') . '" id="add_reminder" value="' . (int) $this->tracker->id . '"></label></div>';
        $output .= '<noscript>
        <p><a href="?action=add_reminder" id="add_reminder">' . $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_add_title') . '</a>
        </noscript>';
        if ($request->get('action') == 'add_reminder') {
            $output .= $this->getNewDateReminderForm($csrf_token);
        } elseif ($request->get('action') == 'update_reminder') {
            $output .= '<div id="update_reminder"></div>';
            $output .= $this->editDateReminder($request->get('reminder_id'), $csrf_token);
        }
        $output .= '</fieldset>';
        echo $output;
    }
}
