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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\DateReminder\DateReminderDao;
use Tuleap\Tracker\Tracker;

class Tracker_DateReminderRenderer // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
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
        $this->dateReminderFactory = new Tracker_DateReminderFactory($this->tracker, $this, new DateReminderDao());
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
        $modal_title = dgettext('tuleap-tracker', 'New date reminder');
        $modal_close = dgettext('tuleap-tracker', 'Close');
        $output      = '<form method="post" id="date-field-reminder-form" class="tlp-modal"> ';
        $output     .= <<<EOS
            <div class="tlp-modal-header">
                <h1 class="tlp-modal-title">$modal_title</h1>
                  <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="$modal_close">
                    <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
                  </button>
            </div>
            EOS;
        $output     .= '<input type="hidden" name="action" value="new_reminder">';
        $output     .= $csrf_token->fetchHTMLInput();
        $output     .= '<div class="tlp-modal-body">';

        $output .= '<div class="tlp-form-element">';
        $output .= '<label class="tlp-label" for="tracker-admin-date-reminder-add-to">' . dgettext(
            'tuleap-tracker',
            'Send an email to'
        ) . '<i class="fa-solid fa-asterisk" aria-hidden="true"></i></label>';
        $output .= $this->getAllowedNotifiedForTracker('tracker-admin-date-reminder-add-to');
        $output .= '</div>';

        $output .= '<div class="tlp-form-element"><label class="tlp-label" for="tracker-admin-date-reminder-add-when">' . dgettext(
            'tuleap-tracker',
            'When'
        ) . '<i class="fa-solid fa-asterisk" aria-hidden="true"></i></label>';
        $output .= '<div class="tracker-admin-date-reminder-when">';
        $output .= '<input type="text" id="tracker-admin-date-reminder-add-when" name="distance" size="3" width="40" class="tlp-input" required/>';
        $output .= dgettext('tuleap-tracker', 'day(s)');
        $output .= '<select name="notif_type" class="tlp-select tlp-select-adjusted">
                        <option value="0"> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_before') . '</option>
                        <option value="1"> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_after') . '</option>
                    </select>';
        $output .= '</div></div>';

        $output .= '<div class="tlp-form-element"><label class="tlp-label" for="tracker-admin-date-reminder-add-field">' . dgettext('tuleap-tracker', 'Field') . '</label>';
        $output .= $this->getTrackerDateFields();
        $output .= '</div>';

        if ($this->getTracker()->hasSemanticsStatus()) {
            $output .= '<input type="hidden" name="notif_closed_artifacts" value="1"/>';
            $output .= '<div class="tlp-form-element"><label class="tlp-label" for="tracker-admin-date-reminder-add-notif">' . dgettext(
                'tuleap-tracker',
                'Notify closed artifacts'
            ) . '</label>';
            $output .= '<select name="notif_closed_artifacts" id="tracker-admin-date-reminder-add-notif" class="tlp-select">
                            <option value="0"> ' . dgettext('tuleap-tracker', 'No') . '</option>
                            <option value="1" selected> ' . dgettext('tuleap-tracker', 'Yes') . '</option>
                        </select></div>';
        }
        $output .= '</div><div class="tlp-modal-footer">';
        $output .= '  <button type="button" data-dismiss="modal" class="tlp-button-primary tlp-button-outline tlp-modal-action">' . dgettext(
            'tuleap-tracker',
            'Cancel'
        ) . '
      </button>';
        $output .= '<button type="submit" name="submit" class="tlp-button-primary" value="' . $GLOBALS['Language']->getText(
            'global',
            'add'
        ) . '"> ' . $GLOBALS['Language']->getText(
            'global',
            'add'
        ) . ' </button> </div>';
        $output .= '</div>';
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
        $javascript_assets = new IncludeAssets(
            __DIR__ . '/../../../scripts/tracker-admin/frontend-assets',
            '/assets/trackers/tracker-admin'
        );

        $GLOBALS['HTML']->includeFooterJavascriptFile(
            $javascript_assets->getFileURL('update-notification-reminder.js')
        );

        $output   = '';
        $reminder = $this->dateReminderFactory->getReminder($reminderId);
        if ($reminder) {
            $notificationType = $reminder->getNotificationType();
            if ($notificationType == Tracker_DateReminder::AFTER) {
                $after  = 'selected';
                $before = '';
            } else {
                $after  = '';
                $before = 'selected';
            }
            $reminderStatus = $reminder->getStatus();
            if ($reminderStatus == Tracker_DateReminder::ENABLED) {
                $enabled  = 'selected';
                $disabled = '';
            } else {
                $enabled  = '';
                $disabled = 'selected';
            }

            $notify_closed_artifacts    = 'selected';
            $notify_only_open_artifacts = '';
            if (! $reminder->mustNotifyClosedArtifacts()) {
                $notify_only_open_artifacts = 'selected';
                $notify_closed_artifacts    = '';
            }

            $purifier = Codendi_HTMLPurifier::instance();

            $modal_title = dgettext('tuleap-tracker', 'Edit reminder');
            $modal_close = dgettext('tuleap-tracker', 'Close');
            $output     .= <<<EOS
                        <div class="tlp-modal"  id="notification-date-reminder-update">
                            <div class="tlp-modal-header">
                                <h1 class="tlp-modal-title">$modal_title</h1>
                                   <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="$modal_close">
                                    <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
                                    </button>
                            </div>
                        <div class="tlp-modal-body">
                     EOS;
            $output     .= '<form method="post" name="update_date_field_reminder">';
            $output     .= '<input type="hidden" name="action" value="update_reminder">';
            $output     .= '<input type="hidden" name="reminder_id" value="' . $purifier->purify($reminderId) . '">
                        <input type="hidden" name="reminder_field_date" value="' . $purifier->purify($reminder->getField()->getId()) . '">';
            $output     .= $csrf_token->fetchHTMLInput();

            $output .= '<div class="tlp-form-element"><label class="tlp-label" for="tracker-admin-date-reminder-update-to">' . dgettext(
                'tuleap-tracker',
                'Send an email to'
            ) . '<i class="fa-solid fa-asterisk" aria-hidden="true"></i></label>';
            $output .= $this->getAllowedNotifiedForTracker('tracker-admin-date-reminder-update-to', $reminderId);
            $output .= '</div>';

            $output .= '<div class="tlp-form-element"><label class="tlp-label" for="tracker-admin-date-reminder-when">' . dgettext('tuleap-tracker', 'When') . '</label>';
            $output .= '<div class="tracker-admin-date-reminder-when">';
            $output .= '<input type="text" name="distance" id="tracker-admin-date-reminder-when" value="' . $reminder->getDistance() . '" size="3" class="tlp-input">';
            $output .= dgettext('tuleap-tracker', 'day(s)');
            $output .= '<select name="notif_type" class="tlp-select tlp-select-adjusted">
                            <option value="0" ' . $before . '> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_before') . '</option>
                            <option value="1" ' . $after . '> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_after') . '</option>
                            </select>';
            $output .= '</div>';
            $output .= '</div>';

            $output .= '<div class="tlp-property"><label class="tlp-label">' . dgettext('tuleap-tracker', 'Field') . '</label>';
            $output .= '<p>' . $purifier->purify($reminder->getField()->getLabel()) . '</p>';
            $output .= '</div>';

            if ($this->getTracker()->hasSemanticsStatus()) {
                $output .= '<input type="hidden" name="notif_closed_artifacts" value="1">';
                $output .= '<div class="tlp-form-element"><label class="tlp-label" for="tracker-admin-date-reminder-notif">' . dgettext(
                    'tuleap-tracker',
                    'Notify closed artifacts'
                ) . '</label>';
                $output .= '<select name="notif_closed_artifacts" id="tracker-admin-date-reminder-notif" class="tlp-select">
                            <option value="0" ' . $notify_only_open_artifacts . '> ' . dgettext('tuleap-tracker', 'No') . '</option>
                            <option value="1" ' . $notify_closed_artifacts . '> ' . dgettext('tuleap-tracker', 'Yes') . '</option>
                        </select>';
                $output .= '</div>';
            }

            $output .= '<div class="tlp-form-element"><label class="tlp-label" for="tracker-admin-date-reminder-status">' . dgettext('tuleap-tracker', 'Status') . '</label>';
            $output .= '<select name="notif_status" id="tracker-admin-date-reminder-status" class="tlp-select"
                            <option value="0" ' . $disabled . '> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_disabled') . '</option>
                            <option value="1" ' . $enabled . '> ' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_enabled') . '</option>
                            </select>';
            $output .= '</div>';

            $output .= '</div><div class="tlp-modal-footer">';
            $output .= '  <button type="button" data-dismiss="modal" class="tlp-button-primary tlp-button-outline tlp-modal-action">' . dgettext(
                'tuleap-tracker',
                'Cancel'
            ) . '
      </button>';
            $output .= '<button class="tlp-button-primary" type="submit" name="submit" value="' . dgettext(
                'tuleap-tracker',
                'Submit Changes'
            ) . '">' . dgettext('tuleap-tracker', 'Submit Changes') . '</button>';
            $output .= '</form></div></div>';
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
    protected function getAllowedNotifiedForTracker(string $id, $reminderId = null)
    {
        /** @psalm-suppress DeprecatedFunction */
        $res             = ugroup_db_get_existing_ugroups($this->tracker->group_id, [$GLOBALS['UGROUP_PROJECT_MEMBERS'],
            $GLOBALS['UGROUP_PROJECT_ADMIN'],
        ]);
        $selectedUgroups = '';
        $ugroups         = [];
        $roles           = [];
        if (! empty($reminderId)) {
            $reminder = $this->dateReminderFactory->getReminder($reminderId);
            $ugroups  = $reminder->getUgroups(true);
            $roles    = $reminder->getRoles();
            if ($roles) {
                foreach ($roles as $role) {
                    $selected[] = $role->getIdentifier();
                }
            }
        }
        $purifier = Codendi_HTMLPurifier::instance();
        $output   = '<select name="reminder_notified[]" id="' . $purifier->purify(
            $id
        ) . '" multiple size=7 class="tlp-select" required>';
        $output  .= '<optgroup label="' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_optgroup_label_ugroup') . '" >';
        while ($row = db_fetch_array($res)) {
            if ($ugroups && in_array($row['ugroup_id'], $ugroups)) {
                $output .= '<option value="u_' . intval($row['ugroup_id']) . '" selected>' . \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) $row['name']) . '</option>';
            } else {
                $output .= '<option value="u_' . intval($row['ugroup_id']) . '">' . \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) $row['name']) . '</option>';
            }
        }
        $output             .= '</optgroup>';
         $output            .= '<optgroup label="' . $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_optgroup_label_role') . '">';
         $all_possible_roles = [
             new Tracker_DateReminder_Role_Submitter(),
             new Tracker_DateReminder_Role_Assignee(),
             new Tracker_DateReminder_Role_Commenter(),
         ];
         $purifier           = Codendi_HTMLPurifier::instance();
         foreach ($all_possible_roles as $role) {
             if ($roles && in_array($role, $roles)) {
                 $output .= '<option value="r_' . $purifier->purify($role->getIdentifier()) . '" selected>' .
                    $purifier->purify($role->getLabel()) . '</option>';
             } else {
                 $output .= '<option value="r_' . $purifier->purify($role->getIdentifier()) . '">' . $purifier->purify($role->getLabel()) . '</option>';
             }
         }
         $output .= '</optgroup>';
         $output .= '</select>';
         return $output;
    }

    /**
     * Build a select box of all date fields used by a given tracker
     */
    protected function getTrackerDateFields(): string
    {
        $purifier          = Codendi_HTMLPurifier::instance();
        $tff               = Tracker_FormElementFactory::instance();
        $trackerDateFields = $tff->getUsedDateFields($this->tracker);
        $ouptut            = '<select name="reminder_field_date" class="tlp-select" id="tracker-admin-date-reminder-add-field">';
        foreach ($trackerDateFields as $dateField) {
            $ouptut .= '<option value="' . $purifier->purify($dateField->getId()) . '">' . $purifier->purify($dateField->getLabel()) . '</option>';
        }
        $ouptut .= '</select>';
        return $ouptut;
    }

    /**
     * Validate date field Id param used for tracker reminder.
     *
     * @param \Tuleap\HTTPRequest $request HTTP request
     *
     * @return int
     */
    public function validateFieldId(\Tuleap\HTTPRequest $request)
    {
        $validFieldId = new Valid_UInt('reminder_field_date');
        $validFieldId->required();
        if ($request->valid($validFieldId)) {
            return $request->get('reminder_field_date');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_field', [$request->get('reminder_field_date')]);
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate distance param used for tracker reminder.
     *
     * @param \Tuleap\HTTPRequest $request HTTP request
     *
     * @return int
     */
    public function validateDistance(\Tuleap\HTTPRequest $request)
    {
        $validDistance = new Valid_UInt('distance');
        $validDistance->required();
        if ($request->valid($validDistance)) {
            return $request->get('distance');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_distance', [$request->get('distance')]);
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate notification type param used for tracker reminder.
     *
     * @param \Tuleap\HTTPRequest $request HTTP request
     *
     * @return int
     */
    public function validateNotificationType(\Tuleap\HTTPRequest $request)
    {
        $validNotificationType = new Valid_UInt('notif_type');
        $validNotificationType->required();
        if ($request->valid($validNotificationType)) {
            return $request->get('notif_type');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_notification_type', [$request->get('notif_type')]);
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate date reminder status.
     *
     * @param \Tuleap\HTTPRequest $request HTTP request
     *
     * @return int
     */
    public function validateStatus(\Tuleap\HTTPRequest $request)
    {
        $validStatus = new Valid_UInt('notif_status');
        $validStatus->required();
        if ($request->valid($validStatus)) {
            return $request->get('notif_status');
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_status', [$request->get('notif_status')]);
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Validate date reminder status.
     *
     * @param \Tuleap\HTTPRequest $request HTTP request
     *
     * @return int
     */
    public function validateNotifyClosedArtifacts(\Tuleap\HTTPRequest $request)
    {
        $validStatus = new Valid_UInt('notif_closed_artifacts');
        $validStatus->required();
        if ($request->valid($validStatus)) {
            return $request->get('notif_closed_artifacts');
        } else {
            $errorMessage = sprintf(
                dgettext('tuleap-tracker', "'%s' is not a valid value for reminder notify closed artifacts"),
                $request->get('notif_closed_artifacts'),
            );
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
        $ugs       = ugroup_db_get_existing_ugroups($groupId, [$GLOBALS['UGROUP_PROJECT_MEMBERS'], $GLOBALS['UGROUP_PROJECT_ADMIN']]);
        $ugroupIds = [];
        while ($row = db_fetch_array($ugs)) {
            $ugroupIds[] = intval($row['ugroup_id']);
        }
        $validUgroupIds = [];
        if (! empty($selectedUgroups)) {
            foreach ($selectedUgroups as $ugroup) {
                if (in_array($ugroup, $ugroupIds)) {
                    $validUgroupIds[] = $ugroup;
                } else {
                    $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_ugroup', [$ugroup]);
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
        $validRoles         = [];
        $all_possible_roles = [
            new Tracker_DateReminder_Role_Submitter(),
            new Tracker_DateReminder_Role_Assignee(),
            new Tracker_DateReminder_Role_Commenter(),
        ];
        foreach ($all_possible_roles as $possible_role) {
            $roles[] = $possible_role->getIdentifier();
        }
        foreach ($selectedRoles as $role) {
            if (in_array($role, $roles)) {
                $validRoles[] = $role;
            } else {
                    $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_invalid_role_param', [$ugroup]);
                    throw new Tracker_DateReminderException($errorMessage);
            }
        }
        return $validRoles;
    }

    /**
     * Scind the notified people for tracker reminder into dedicated arrays.
     * At least one list should be not empty
     *
     * @param \Tuleap\HTTPRequest $request HTTP request
     *
     * @return Array
     */
    public function scindReminderNotifiedPeople(\Tuleap\HTTPRequest $request)
    {
        $vArray   = new Valid_Array('reminder_notified');
        $notified = $roles = $ugroups = [];
        if ($request->valid($vArray)) {
            $people = $request->get('reminder_notified');
            if ($people) {
                foreach ($people as $value) {
                    if ($value[0] == 'r') {
                        $roles[] = substr($value, 2);
                    } else {
                        $ugroups[] = substr($value, 2);
                    }
                }
            }
            if (! empty($ugroups) || ! empty($roles)) {
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
     */
    public function displayAllReminders(): string
    {
        $send_email_to_column_title = dgettext('tuleap-tracker', 'Send an email to');
        $when_column_title          = dgettext('tuleap-tracker', 'When');
        $field_column_title         = dgettext('tuleap-tracker', 'Field');
        $purifier                   = Codendi_HTMLPurifier::instance();
        if ($this->getTracker()->hasSemanticsStatus()) {
            $notified_closed_artifact_column_title = dgettext('tuleap-tracker', 'Notify closed artifacts');
            $output                                = <<<EOS
                <table class="tlp-table">
                    <thead>
                        <tr>
                            <th>
                                $send_email_to_column_title
                            </th>
                            <th>$when_column_title</th>
                            <th>$field_column_title</th>
                            <th>$notified_closed_artifact_column_title</th>
                            <th></th>
                        </tr>
                    </thead>
            <tbody>
            EOS;
        } else {
            $output = <<<EOS
                <table class="tlp-table">
                    <thead>
                        <tr>
                            <th>$send_email_to_column_title</th>
                            <th>$when_column_title</th>
                            <th>$field_column_title</th>
                            <th></th>
                        </tr>
                    </thead>
            <tbody>
            EOS;
        }

        $trackerReminders = $this->dateReminderFactory->getTrackerReminders(true);
        if (! empty($trackerReminders)) {
            foreach ($trackerReminders as $reminder) {
                if ($reminder->getStatus() == Tracker_DateReminder::ENABLED) {
                    $output .= '<tr>';
                } else {
                    $output .= '<tr class="tracker_date_reminder">';
                }
                $output .= '<td>' . $reminder->getUgroupsLabel();
                $output .= $reminder->getRolesLabel() . '</td>';
                $output .= '<td>' . sprintf(dgettext('tuleap-tracker', '%1$s day(s) %2$s'), $reminder->getDistance(), $reminder->getNotificationTypeLabel()) . '</td>';
                $output .= '<td>' . $purifier->purify($reminder->getField()->getLabel()) . '</td>';

                if ($this->getTracker()->hasSemanticsStatus()) {
                    $display_closed_artifact_value = $reminder->mustNotifyClosedArtifacts() ? dgettext('tuleap-tracker', 'Yes') : dgettext('tuleap-tracker', 'No');
                    $output                       .= '<td>' . $display_closed_artifact_value . '</td>';
                }

                $output .= '<td class="tlp-table-cell-actions"> <a class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline" href="?reminder_id=' . (int) $reminder->getId(
                ) . '&amp;action=update_reminder" id="update_reminder"> <i class="fa-solid fa-pencil-alt" aria-hidden="true"></i>' . dgettext(
                    'tuleap-tracker',
                    'Edit'
                ) . '</a>';
                $output .= '<a class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline" href="?action=delete_reminder&amp;reminder_id=' . $reminder->getId(
                ) . '" id="delete_reminder"> <i class="fa-regular fa-trash-alt" aria-hidden="true"></i>' . dgettext(
                    'tuleap-tracker',
                    'Delete'
                ) . '</a></td>';
                $output .= '</tr>';
            }
        } else {
            $colspan = $this->getTracker()->hasSemanticsStatus() ? 5 : 4;
            $output .= '<tr><td colspan="' . $colspan . '" class="tlp-table-cell-empty">';
            $output .= dgettext('tuleap-tracker', 'No date reminder defined for this tracker');
            $output .= '</td></tr>';
        }

        $output .= '</tbody></table>';

        return $output;
    }

    /**
     * Ask for confirmation before deleting a given date reminder
     */
    public function displayConfirmDelete(int $reminderId, CSRFSynchronizerToken $csrf_token): string
    {
        $javascript_assets = new IncludeAssets(
            __DIR__ . '/../../../scripts/tracker-admin/frontend-assets',
            '/assets/trackers/tracker-admin'
        );

        $GLOBALS['HTML']->includeFooterJavascriptFile(
            $javascript_assets->getFileURL('delete-notification-reminder.js')
        );


        $purifier        = Codendi_HTMLPurifier::instance();
        $reminder        = $this->dateReminderFactory->getReminder($reminderId);
        $reminderString  = '<b>' . dgettext('tuleap-tracker', 'Send an email to');
        $reminderString .= '&nbsp;' . $reminder->getUgroupsLabel() . '&nbsp;';
        $reminderString .= sprintf(dgettext('tuleap-tracker', '%1$s day(s) %2$s'), $reminder->getDistance(), $reminder->getNotificationTypeLabel()) . '&nbsp;"';
        $reminderString .= $purifier->purify($reminder->getField()->getLabel()) . '"</b>';

        $modal_title          = dgettext('tuleap-tracker', 'Confirm deletion of date reminder');
        $deletion_description = sprintf(
            dgettext(
                'tuleap-tracker',
                '<p>You are going to delete this date reminder:</p><p>%1$s</p><p>Are you sure that you want to continue?</p>'
            ),
            $reminderString
        );
        $modal_close          = dgettext('tuleap-tracker', 'Close');

        $output  = <<<EOS
                        <div class="tlp-modal tlp-modal-danger" id="notification-date-reminder-delete">
                            <div class="tlp-modal-header">
                                <h1 class="tlp-modal-title">$modal_title</h1>
                                  <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="$modal_close">
                                    <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
                                 </button>
                            </div>
                           <div class="tlp-modal-body">
                                 <form id="delete_reminder" method="POST" class="date_reminder_confirm_delete">
                                    $deletion_description
                    EOS;
        $output .= $csrf_token->fetchHTMLInput();
        $output .= '</div><div class="tlp-modal-footer">';
        $output .= '<input type="hidden" name="action" value="confirm_delete_reminder" />';
        $output .= '<input type="hidden" name="tracker" value="' . $purifier->purify((int) $this->tracker->id) . '" />';
        $output .= '<input type="hidden" name="reminder_id" value="' . $purifier->purify($reminderId) . '" />';
        $output .= '<input class="tlp-button-danger tlp-button-outline tlp-modal-action " type="submit" name="cancel_delete_reminder" value="' . dgettext(
            'tuleap-tracker',
            'No, I do not want to delete it'
        ) . '" />';
        $output .= '<input class="tlp-button-danger tlp-modal-action" type="submit" name="confirm_delete" value="' . dgettext(
            'tuleap-tracker',
            'Yes, I am sure!'
        ) . '" />';
        $output .= '</div>';
        $output .= '</div></form></div>';
        return $output;
    }

    public function displayDateReminders(\Tuleap\HTTPRequest $request, CSRFSynchronizerToken $csrf_token): void
    {
        $title                     = $GLOBALS['Language']->getText(
            'project_admin_utils',
            'tracker_date_reminder_title'
        );
        $description               = dgettext(
            'tuleap-tracker',
            'Please note that date reminders does not take tracker notifications settings into account and will always be sent even if notifications are disabled.'
        );
        $add_reminder_button_label = dgettext('tuleap-tracker', 'Add reminder');

        $output  = <<<EOS
        <div class="tlp-framed-horizontally">
            <div class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">$title</h1>
                    </div>
                    <div class="tlp-pane-section">
                    <p class="tlp-text">$description</p>
                    <div class="tlp-table-actions">
                          <button class="tlp-button-primary tlp-table-actions-element" id="add-reminder-button" data-target-modal-id="date-field-reminder-form">
                          <i class="tlp-button-icon fa-solid fa-plus" aria-hidden="true"></i> $add_reminder_button_label
                          </button>
                    </div>
        EOS;
        $output .= '<fieldset>';
        if ($request->get('action') == 'delete_reminder') {
            $output .= $this->displayConfirmDelete($request->get('reminder_id'), $csrf_token);
        }
        $output .= $this->displayAllReminders();
        $output .= $this->getNewDateReminderForm($csrf_token);
        if ($request->get('action') == 'update_reminder') {
            $output .= '<div id="update_reminder"></div>';
            $output .= $this->editDateReminder($request->get('reminder_id'), $csrf_token);
        }
        $output .= '</fieldset>';
        $output .= '</div></div></div></div>';
        echo $output;
    }
}
