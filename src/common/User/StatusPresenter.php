<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\User;

use PFUser;

class StatusPresenter
{
    public $status_label;
    public $status_level;
    public $status_is_important = false;
    public $status_is_active = false;

    public function __construct($status)
    {
        switch ($status) {
            case PFUser::STATUS_ACTIVE:
                $this->status_label        = $GLOBALS['Language']->getText('admin_userlist', 'active');
                $this->status_level        = 'success';
                $this->status_is_active    = true;
                break;
            case PFUser::STATUS_RESTRICTED:
                $this->status_label        = $GLOBALS['Language']->getText('admin_userlist', 'restricted');
                $this->status_level        = 'warning';
                break;
            case PFUser::STATUS_DELETED:
                $this->status_label        = $GLOBALS['Language']->getText('admin_userlist', 'deleted');
                $this->status_level        = 'danger';
                break;
            case PFUser::STATUS_SUSPENDED:
                $this->status_label        = $GLOBALS['Language']->getText('admin_userlist', 'suspended');
                $this->status_level        = 'secondary';
                break;
            case PFUser::STATUS_PENDING:
                $this->status_label        = $GLOBALS['Language']->getText('admin_userlist', 'pending');
                $this->status_level        = 'info';
                $this->status_is_important = true;
                break;
            case PFUser::STATUS_VALIDATED:
                $this->status_label        = $GLOBALS['Language']->getText('admin_userlist', 'validated');
                $this->status_level        = 'info';
                $this->status_is_important = true;
                break;
            case PFUser::STATUS_VALIDATED_RESTRICTED:
                $this->status_label        = $GLOBALS['Language']->getText('admin_userlist', 'validated_restricted');
                $this->status_level        = 'info';
                $this->status_is_important = true;
                break;
        }
    }
}
