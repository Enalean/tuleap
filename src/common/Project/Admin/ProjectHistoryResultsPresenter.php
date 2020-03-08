<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Project\Admin;

use TemplateSingleton;
use UserManager;
use UserHelper;

class ProjectHistoryResultsPresenter
{
    public $history;
    public $total_rows;

    public function __construct($results)
    {
        $this->history    = $this->getHistoryResultPresenter($results['history']);
        $this->total_rows = $results['numrows'];
    }

    private function getHistoryResultPresenter($history)
    {
        $presenters = array();

        foreach ($history as $row) {
            $field = $row['field_name'];

            // see if there are any arguments after the message key
            // format is "msg_key ## arg1||arg2||...
            // If msg_key cannot be found in the localized message
            // catalog then display the msg has is because this is very
            // likely a legacy message (pre-localization version)
            $arr_args = '';
            if (strpos($field, " %% ") !== false) {
                list($msg_key, $args) = explode(" %% ", $field);
                if ($args) {
                    $arr_args = explode('||', $args);
                }
            } else {
                $msg_key  = $field;
                $arr_args = "";
            }
            $msg = $GLOBALS['Language']->getText('project_admin_utils', $msg_key, $arr_args);
            if (strpos($msg, "*** Unkown msg") !== false) {
                $msg = $field;
            }

            $value = $this->getEventValue($row, $msg_key);
            $user  = UserManager::instance()->getUserByUserName($row['user_name']);

            $presenters[] = array(
                'event' => $msg,
                'value' => $value,
                'date'  => format_date($GLOBALS['Language']->getText('system', 'datefmt'), $row['date']),
                'user'  => array(
                    'id'           => $user->getId(),
                    'display_name' => UserHelper::instance()->getDisplayNameFromUser($user),
                    'is_none'      => $user->isNone()
                )
            );
        }

        return $presenters;
    }

    private function getEventValue($row, $msg_key)
    {
        $val = $row['old_value'];

        if (strstr($msg_key, "perm_granted_for_")
            || strstr($msg_key, "perm_reset_for_")
            || strstr($msg_key, "membership_request_updated")
        ) {
            $ugroup_list = explode(",", $val);
            $val = '';
            foreach ($ugroup_list as $ugroup) {
                if ($val) {
                    $val .= ', ';
                }
                $val .= util_translate_name_ugroup($ugroup);
            }
        } elseif ($msg_key == "group_type") {
            $val = TemplateSingleton::instance()->getLabel($val);
        }

        return $val;
    }
}
