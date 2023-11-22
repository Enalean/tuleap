<?php
/**
 * Copyright Enalean (c) 2017-Present. All rights reserved.
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

namespace Tuleap\Notification;

use UserHelper;

class UserInvolvedInNotificationPresenter
{
    public $avatar_url;
    public $label;
    public $user_id;

    public function __construct(
        $user_id,
        $user_name,
        $real_name,
        $avatar_url,
    ) {
        $this->user_id    = $user_id;
        $this->avatar_url = $avatar_url;
        $this->label      = UserHelper::instance()->getDisplayName($user_name, $real_name);
    }
}
