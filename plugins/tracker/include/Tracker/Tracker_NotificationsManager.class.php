<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */



class Tracker_NotificationsManager {

    protected $tracker;

    public function __construct($tracker) {
        $this->tracker = $tracker;
    }

    public function process(TrackerManager $tracker_manager, Codendi_Request $request, $current_user) {
        if ($request->exist('stop_notification')) {
            if ($this->tracker->stop_notification != $request->get('stop_notification')) {
                $this->tracker->stop_notification = $request->get('stop_notification') ? 1 : 0;
                $dao                              = new TrackerDao();
                if ($dao->save($this->tracker)) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_notification', 'successfully_updated'));
                }
            }
        }

        if ($global_notification_data = $request->get('global_notification')) {
            if (!empty($global_notification_data)) {
                $this->processGlobalNotificationData($global_notification_data);
            }
        }

        $this->deleteGlobalNotification($request);

        $this->displayAdminNotifications($tracker_manager, $request, $current_user);
        $reminderRenderer = new Tracker_DateReminderRenderer($this->tracker);

        if ($this->tracker->userIsAdmin($current_user)) {
            $reminderRenderer->displayDateReminders($request);
        }

        $reminderRenderer->displayFooter($tracker_manager);
    }

    private function deleteGlobalNotification(Codendi_Request $request)
    {
        if ($request->exist('remove_global')) {
            foreach ($request->get('remove_global') as $notification_id => $value) {
                $this->removeGlobalNotification($notification_id);
            }
        }
    }

    protected function displayAdminNotifications(TrackerManager $tracker_manager, $request, $current_user) {
        $hp = Codendi_HTMLPurifier::instance();
        $this->tracker->displayAdminItemHeader($tracker_manager, 'editnotifications');
        echo '<fieldset><form action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->tracker->id .'&amp;func=admin-notifications" method="POST">';

        $this->displayAdminNotifications_Toggle();
        $this->displayAdminNotifications_Global($request);
        //TODO
        //$this->displayAdminNotifications_Personnal($current_user);

        echo'
        <HR>
        <P align="center"><INPUT type="submit" name="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','submit').'">
        </FORM></fieldset>';
    }

    protected function displayAdminNotifications_Toggle() {
        $hp = Codendi_HTMLPurifier::instance();

        if ($this->tracker->userIsAdmin()) {
            echo '<h3><a name="ToggleEmailNotification"></a>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','toggle_notification').' '.
            help_button('tracker.html#e-mail-notification').'</h3>';
            echo '
                <p>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','toggle_notif_note').'<br>
                <br><input type="hidden" name="stop_notification" value="0" /> 
                <label class="checkbox"><input id="toggle_stop_notification" type="checkbox" name="stop_notification" value="1" '.(($this->tracker->stop_notification)?'checked="checked"':'').' /> '.
                $GLOBALS['Language']->getText('plugin_tracker_include_type','stop_notification') .'</label>';
        } else if ($this->tracker->stop_notification) {
            echo '<h3><a name="ToggleEmailNotification"></a>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','notification_suspended').' '.
            help_button('tracker.html#e-mail-notification').'</h3>';
            echo '
            <P><b>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','toggle_notif_warn').'</b><BR>';
        }
    }

    protected function displayAdminNotifications_Global(HTTPRequest $request) {
        $hp = Codendi_HTMLPurifier::instance();
        echo '<h3><a name="GlobalEmailNotification"></a>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','global_mail_notif').' '.
        help_button('tracker.html#e-mail-notification').'</h3>';

        $notifs    = $this->getGlobalNotifications();
        $nb_notifs = count($notifs);
        if ($this->tracker->userIsAdmin()) {
            echo '<p>'. $GLOBALS['Language']->getText('plugin_tracker_include_type','admin_note') .'</p>';
            $id        = 0;
            echo '<table id="global_notifs" class="table table-bordered">';
            echo '<thead><tr>';
            echo '<th><i class="icon-trash"></i></th>';
            echo '<th class="plugin-tracker-global-notifs-people">'. dgettext('tuleap-tracker', 'Notified people') .'</th>';
            echo '<th class="plugin-tracker-global-notifs-updates">'. $GLOBALS['Language']->getText('plugin_tracker_include_type','send_all') .'</th>';
            echo '<th class="plugin-tracker-global-notifs-permissions">'. $GLOBALS['Language']->getText('plugin_tracker_include_type','check_perms') .'</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            foreach($notifs as $key => $nop) {
                $id                = (int)$nop->getId();
                $addresses         = $nop->getAddresses();
                $all_updates       = $nop->isAllUpdates();
                $check_permissions = $nop->isCheckPermissions();
                echo '<tr>';
                echo $this->getGlobalNotificationForm( $id, $addresses, $all_updates, $check_permissions );
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo '<p><a href="?func=admin-notifications&amp;tracker='. (int)$this->tracker->id .'&amp;action=add_global" id="add_global">'. $GLOBALS['Language']->getText('plugin_tracker_include_type','add') .'</a></p>';
            echo '<script type="text/javascript">'."
            document.observe('dom:loaded', function() {
                $('add_global').observe('click', function (evt) {
                    var self = arguments.callee;
                    if (!self.counter) {
                        self.counter = $id;
                    }
                    self.counter++;
                    var number = self.counter;
                    
                    var line = new Element('tr');
                    line.insert('".$this->getGlobalNotificationForm($id="'+number+'", $addresses='', $all_updates=1, $check_permissions=0)."');
                    
                    var tbody = document.querySelector('#global_notifs > tbody');
                    Element.insert(tbody, line);
                    
                    Event.stop(evt);
                    return false;
                });
            });
            </script>";
        } else {
            $ok = false;
            if ( $nb_notifs ) {
                reset($notifs);
                while(!$ok && (list($id,) = each($notifs))) {
                    $ok = $notifs[$id]->getAddresses();
                }
            }
            if ($ok) {
                echo $GLOBALS['Language']->getText('plugin_tracker_include_type','admin_conf');
                foreach($notifs as $key => $nop) {
                    if ($notifs[$key]->getAddresses()) {
                        echo '<div>'. $notifs[$key]->getAddresses() .'&nbsp;&nbsp;&nbsp; ';
                        echo $GLOBALS['Language']->getText('plugin_tracker_include_type','send_all_or_not',($notifs[$key]->isAllUpdates()?$GLOBALS['Language']->getText('global','yes'):$GLOBALS['Language']->getText('global','no')));
                        echo '</div>';
                    }
                }
            } else {
                echo $GLOBALS['Language']->getText('plugin_tracker_include_type','admin_not_conf');
            }
        }
    }

    protected function getGlobalNotificationForm($id, $addresses, $all_updates, $check_permissions)
    {
        $output  = '';
        $output .= '<td>';
        $output .= '<input type="checkbox" name="remove_global['.$id.']" />';
        $output .= '</td>';
        //addresses
        $output .= '<td>';
        $output .= '<input type="text" name="global_notification['.$id.'][addresses]" value="'. Codendi_HTMLPurifier::instance()->purify($addresses, CODENDI_PURIFIER_CONVERT_HTML)  .'" size="55" />';
        $output .= '</td>';
        //all_updates
        $output .= '<td class="tracker-global-notifications-checkbox-cell">';
        $output .= '<input type="hidden" name="global_notification['.$id.'][all_updates]" value="0" />';
        $output .= '<input type="checkbox" name="global_notification['.$id.'][all_updates]" value="1" '.($all_updates ? 'checked="checked"' : '').'/>';
        $output .= '</td>';
        //check_permissions
        $output .= '<td class="tracker-global-notifications-checkbox-cell">';
        $output .= '<input type="hidden" name="global_notification['.$id.'][check_permissions]" value="0" />';
        $output .= '<input type="checkbox" name="global_notification['.$id.'][check_permissions]" value="1" '.( $check_permissions ? 'checked="checked"' : '').'/>';
        $output .= '</td>';

        return $output;
    }

    protected function displayAdminNotifications_Personnal($current_user) {
        $user_id = $current_user->getId();
        $hp = Codendi_HTMLPurifier::instance();

        // Build Wachees UI
        $arr_watchees = array();
        foreach($this->getWatcherDao()->searchWatchees($this->tracker->id, $current_user->getId()) as $row) {
            $arr_watchees[] = user_getname($row['watchee_id']);
        }
        $watchees = join(',',$arr_watchees);

        echo '<h3>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','perso_mail_notif').'</h3>';

        if ($this->tracker->userIsAdmin()) {
            // To watch other users you must have at least admin rights on the tracker
            echo'
            <h4>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','users_to_watch').' '.
            help_button('tracker.html#email-notification-settings').'</h4>
            <P>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','backup_person').'
            <p><INPUT TYPE="TEXT" NAME="watchees" VALUE="'. $hp->purify($watchees, CODENDI_PURIFIER_CONVERT_HTML) .'" SIZE="55" MAXLENGTH="255"><br></p>
            ';

            $watchers="";
            foreach($this->getWatcherDao()->searchWatchers($this->tracker->id, $current_user->getId()) as $row) {
                $watcher_name = user_getname($row_watcher['user_id']);
                $watchers .= '<a href="/users/'.urlencode($watcher_name).'">'. $hp->purify($watcher_name, CODENDI_PURIFIER_CONVERT_HTML) .'</a>,';
            }
            $watchers = substr($watchers,0,-1); // remove extra comma at the end

            if ($watchers) {
                echo "<p>".$GLOBALS['Language']->getText('plugin_tracker_include_type','watchers', $hp->purify($watchers, CODENDI_PURIFIER_CONVERT_HTML) );
            } else {
                echo "<p>".$GLOBALS['Language']->getText('plugin_tracker_include_type','no_watcher');
            }
            echo '<br><br>';
        }

        // Build Role/Event table
        $dar_roles = $this->getNotificationDao()->searchRoles($this->tracker->id);
        $num_roles  = $dar_roles->rowCount();
        $dar_events = $this->getNotificationDao()->searchEvents($this->tracker->id);
        $num_events  = $dar_events->rowCount();

        $arr_notif = array();
        // By default it's all 'yes'
        foreach($dar_roles as $role) {
            foreach($dar_events as $event) {
                $arr_notif[$role['role_label']][$event['event_label']] = 1;
            }
        }

        foreach($this->getNotificationDao()
                     ->searchNotification($this->tracker->id, $current_user->getId()) as $arr) {
            $arr_notif[$arr['role_label']][$arr['event_label']] = $arr['notify'];
        }

        // Rk: Can't use html_build_list_table_top because of the specific layout
        echo '<h4>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','event_settings').' '.
        help_button('tracker.html#email-notification-settings').'</h4>
                      <P>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','tune_settings');

        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <table BORDER="0" CELLSPACING="1" CELLPADDING="2" class="small">
        <tr class="boxtitle">
            <td colspan="'.(int)$num_roles.'" align="center" width="50%"><b>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','role_is').'</b></td>
            <td rowspan="2" width="50%"><b>&nbsp;&nbsp;&nbsp;'.$GLOBALS['Language']->getText('plugin_tracker_include_type','notify_me').'</b></td>
        </tr>';

        $dar_roles->rewind();
        foreach($dar_roles as $role) {
            echo '<td align="center" width="10%"><b>'.$GLOBALS['Language']->getText('plugin_tracker_common_types',$role['short_description_msg'])."</b></td>\n";
        }
        echo "</tr>\n";

        $dar_events->rewind();
        $dar_roles->rewind();
        $i = 0;
        foreach($dar_events as $event) {
            $event_label = $event['event_label'];
            echo "<tr class=\"".util_get_alt_row_color($i++)."\">\n";
            foreach($dar_roles as $role) {
                $role_label = $role['role_label'];
                $cbox_name = 'cb_'.$role['role_id'].'_'.$event['event_id'];

                if ( (($event_label == 'NEW_ARTIFACT') && ($role_label != 'ASSIGNEE') && ($role_label != 'SUBMITTER')) ||
                    (($event_label == 'ROLE_CHANGE') && ($role_label != 'ASSIGNEE') && ($role_label != 'CC')) ) {
                    // if the user is not a member then the ASSIGNEE column cannot
                    // be set. If it's not an assignee or a submitter the new_artifact event is meaningless
                    echo '   <td align="center"><input type="hidden" name="'.$cbox_name.'" value="1">-</td>'."\n";
                } else {
                    echo '   <td align="center"><input type="checkbox" name="'.$cbox_name.'" value="1" '.
                    ($arr_notif[$role_label][$event_label] ? 'checked':'')."></td>\n";
                }
            }
            echo '   <td>&nbsp;&nbsp;&nbsp;'.$GLOBALS['Language']->getText('plugin_tracker_common_types',$event['description_msg'])."</td>\n";
            echo "</tr>\n";
        }

        echo'
        </table>';
    }

    /**
     * this function process global notification data
     * @param Array<Array> $data
     */
    protected function processGlobalNotificationData($data) {
        $global_notifications = $this->getGlobalNotifications();
        foreach ( $data as $id=>$fields ) {
            if ( empty($fields['addresses']) ) {
                continue;
            }
            if ( !isset($fields['all_updates']) ) {
                continue;
            }
            if ( !isset($fields['check_permissions']) ) {
                continue;
            }
            if ( array_key_exists($id, $global_notifications) ) {
                $this->updateGlobalNotification($id, $fields);
            } else {
                $this->addGlobalNotification($fields['addresses'], $fields['all_updates'], $fields['check_permissions']);
            }
        }

    }

    public function getGlobalNotifications() {
        $notifs = array();
        foreach($this->getGlobalDao()->searchByTrackerId($this->tracker->id) as $row) {
            $notifs[$row['id']] = new Tracker_GlobalNotification($row);
        }
        return $notifs;
    }

    /**
     *
     * @param String $addresses
     * @param Integer $all_updates
     * @param Integer $check_permissions
     * @return Integer last inserted id in database
     */
    protected function addGlobalNotification( $addresses, $all_updates, $check_permissions ) {
        return $this->getGlobalDao()->create($this->tracker->id, $addresses, $all_updates, $check_permissions);
    }

    protected function removeGlobalNotification($id)
    {
        $dao   = $this->getGlobalDao();
        $notif = $dao->searchById($id);

        if (! empty($notif)) {
            $dao->delete($id, $this->tracker->id);
        }
    }

    protected function updateGlobalNotification($global_notification_id, $data) {
        $feedback = '';
        $arr_email_address = preg_split('/[,;]/', $data['addresses']);
        if (!util_validateCCList($arr_email_address, $feedback, false)) {
          $GLOBALS['Response']->addFeedback('error', $feedback);
        } else {
          $data['addresses'] = util_cleanup_emails(implode(', ', $arr_email_address));
          return $this->getGlobalDao()->modify($global_notification_id, $data);
        }
        return false;
    }

    /**
     * @param boolean $update true if the action is an update one (update artifact, add comment, ...) false if it is a create action.
     */
    public function getAllAddresses($update = false) {
        $addresses = array();
        $notifs = $this->getGlobalNotifications();
        foreach($notifs as $key => $nop) {
            if (!$update || $notifs[$key]->isAllUpdates()) {
                foreach(preg_split('/[,;]/', $notifs[$key]->getAddresses()) as $address) {
                    $addresses[] = array('address' => $address, 'check_permissions' => $notifs[$key]->isCheckPermissions());
                }
            }
        }
        return $addresses;
    }

    protected function getGlobalDao() {
        return new Tracker_GlobalNotificationDao();
    }

    protected function getWatcherDao() {
        return new Tracker_WatcherDao();
    }

    protected function getNotificationDao() {
        return new Tracker_NotificationDao();
    }

    public static function isMailingList($email_address) {
        $r = preg_match_all('/\S+\@lists\.\S+/', $subject, $matches);
        if ( !empty($r)  ) {
            return true;
        }
        return false;
    }
}
?>
