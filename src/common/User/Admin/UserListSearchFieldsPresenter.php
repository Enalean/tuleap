<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\User\Admin;

use PFUser;

class UserListSearchFieldsPresenter
{
    private static $ANY = 'ANY';

    public $name;
    public $name_label;
    public $status_label;
    public $status_values;
    public $search;

    public function __construct($name, $status_values)
    {
        $this->name       = $name;
        $this->name_label = $GLOBALS['Language']->getText('admin_userlist', 'filter_name');

        $this->status_label  = $GLOBALS['Language']->getText("admin_userlist", "status");
        $this->status_values = $this->getListOfStatusValuePresenter($status_values);

        $this->title  = $GLOBALS['Language']->getText('global', 'search_title');
        $this->search = $GLOBALS['Language']->getText('global', 'btn_search');
    }

    private function getListOfStatusValuePresenter($status_values)
    {
        return array(
            $this->getStatusValuePresenter(self::$ANY, $status_values, $GLOBALS['Language']->getText("admin_userlist", "any")),
            $this->getStatusValuePresenter(PFUser::STATUS_ACTIVE, $status_values, $GLOBALS['Language']->getText("admin_userlist", "active")),
            $this->getStatusValuePresenter(PFUser::STATUS_RESTRICTED, $status_values, $GLOBALS['Language']->getText("admin_userlist", "restricted")),
            $this->getStatusValuePresenter(PFUser::STATUS_DELETED, $status_values, $GLOBALS['Language']->getText("admin_userlist", "deleted")),
            $this->getStatusValuePresenter(PFUser::STATUS_SUSPENDED, $status_values, $GLOBALS['Language']->getText("admin_userlist", "suspended")),
            $this->getStatusValuePresenter(PFUser::STATUS_PENDING, $status_values, $GLOBALS['Language']->getText("admin_userlist", "pending")),
            $this->getStatusValuePresenter(PFUser::STATUS_VALIDATED, $status_values, $GLOBALS['Language']->getText("admin_userlist", "validated")),
            $this->getStatusValuePresenter(PFUser::STATUS_VALIDATED_RESTRICTED, $status_values, $GLOBALS['Language']->getText("admin_userlist", "validated_restricted"))
        );
    }

    private function getStatusValuePresenter($status, $status_values, $label)
    {
        $selected = false;
        if ($status === self::$ANY) {
            $selected = count($status_values) === 0;
        } else {
            $selected = in_array($status, $status_values);
        }

        return array(
            'value'       => $status,
            'is_selected' => $selected,
            'label'       => $label
        );
    }
}
