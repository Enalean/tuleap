<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\ProFTPd\Xferlog;

class Entry
{

    /** @var int */
    public $current_time;

    /** @var int */
    public $transfer_time;

    /** @var string */
    public $remote_host;

    /** @var int */
    public $file_size;

    /** @var string */
    public $filename;

    /** @var string */
    public $transfer_type;

    /** @var string */
    public $special_action_flag;

    /** @var string */
    public $direction;

    /** @var string */
    public $access_mode;

    /** @var string */
    public $username;

    /** @var string */
    public $service_name;

    /** @var string */
    public $authentication_method;

    /** @var string */
    public $authenticated_user_id;

    /** @var string */
    public $completion_status;

    public function __construct(
        $current_time,
        $transfer_time,
        $remote_host,
        $file_size,
        $filename,
        $transfer_type,
        $special_action_flag,
        $direction,
        $access_mode,
        $username,
        $service_name,
        $authentication_method,
        $authenticated_user_id,
        $completion_status
    ) {
        $this->current_time          = $current_time;
        $this->transfer_time         = $transfer_time;
        $this->remote_host           = $remote_host;
        $this->file_size             = $file_size;
        $this->filename              = $filename;
        $this->transfer_type         = $transfer_type;
        $this->special_action_flag   = $special_action_flag;
        $this->direction             = $direction;
        $this->access_mode           = $access_mode;
        $this->username              = $username;
        $this->service_name          = $service_name;
        $this->authentication_method = $authentication_method;
        $this->authenticated_user_id = $authenticated_user_id;
        $this->completion_status     = $completion_status;
    }
}
