<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2015. All Rights Reserved.
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

require_once __DIR__ . '/../../www/project/export/project_export_utils.php';

class Admin_UserListExporter
{

    /**
     * @var array
     */
    private $col_list = [
        'user_id',
        'login_name',
        'real_name',
        'email',
        'member_of',
        'admin_of',
        'status',
        'last_access_date',
    ];

    /**
     * Export user list in csv format
     *
     * @param int    $group_id
     * @param String $user_name_search
     * @param String $current_sort_header
     * @param String $sort_order
     * @param array  $status_values
     */
    public function exportUserList($group_id, $user_name_search, $current_sort_header, $sort_order, $status_values)
    {
        global $Language;
        header('Content-Type: text/csv');
        header('Content-Disposition:attachment; filename=users_list.csv');
        $eol = "\n";
        $documents_title = [
            'user_id'          => $Language->getText('admin_userlist', 'id_user'),
            'login_name'       => $Language->getText('include_user_home', 'login_name'),
            'real_name'        => $Language->getText('include_user_home', 'real_name'),
            'email'            => $Language->getText('admin_userlist', 'email'),
            'member_of'        => $Language->getText('admin_userlist', 'member_of'),
            'admin_of'         => $Language->getText('admin_userlist', 'admin_of'),
            'status'           => $Language->getText('admin_userlist', 'status'),
            'last_access_date' => $Language->getText('admin_userlist', 'last_access_date'),
        ];
        echo build_csv_header($this->col_list, $documents_title) . $eol;
        $dao = new UserDao(CodendiDataAccess::instance());
        $result = $dao->listAllUsers($group_id, $user_name_search, 0, 0, $current_sort_header, $sort_order, $status_values);
        $users  = $result['users'];
        echo $this->buildCsvBody($users);
    }

    /**
     * Build the body of csv file
     *
     * @param array $users
     *
     */
    private function buildCsvBody($users)
    {
        $csv_body = "";
        $hp = Codendi_HTMLPurifier::instance();
        foreach ($users as $user) {
            $documents_body = [
                'user_id'          => $user['user_id'],
                'login_name'       => $hp->purify($user['user_name']),
                'real_name'        => $hp->purify($user['realname']),
                'email'            => $hp->purify($user['email']),
                'member_of'        => $user['member_of'],
                'admin_of'         => $user['admin_of'],
                'status'           => $this->getUserStatus($user['status']),
                'last_access_date' => $this->getLastAccessDate($user['last_access_date']),
            ];
            $csv_body .= build_csv_record($this->col_list, $documents_body) . "\n";
        }

        return $csv_body;
    }

    private function getLastAccessDate($last_access_date_timestamp)
    {
        if ((int) $last_access_date_timestamp === 0) {
            return $GLOBALS['Language']->getText('admin_userlist', 'never_logged');
        }
        return format_date(util_get_user_preferences_export_datefmt(), $last_access_date_timestamp);
    }

    /**
     * Return user status from status_code
     *
     * @param string $status_code
     *
     */
    private function getUserStatus($status_code)
    {
        global $Language;
        switch ($status_code) {
            case PFUser::STATUS_ACTIVE:
                $status = $Language->getText('admin_userlist', 'active');
                break;
            case PFUser::STATUS_RESTRICTED:
                $status = $Language->getText('admin_userlist', 'restricted');
                break;
            case PFUser::STATUS_DELETED:
                $status = $Language->getText('admin_userlist', 'deleted');
                break;
            case PFUser::STATUS_SUSPENDED:
                $status = $Language->getText('admin_userlist', 'suspended');
                break;
            case PFUser::STATUS_PENDING:
                $status = $Language->getText('admin_userlist', 'pending');
                break;
            case PFUser::STATUS_VALIDATED:
                $status = $Language->getText('admin_userlist', 'validated');
                break;
            case PFUser::STATUS_VALIDATED_RESTRICTED:
                $status = $Language->getText('admin_userlist', 'validated_restricted');
                break;
        }
        return $status;
    }
}
