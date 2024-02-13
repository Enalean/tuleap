<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TestManagement\RealTime;

use Tuleap\User\REST\UserRepresentation;

/**
 * @psalm-immutable
 */
final class RealtimeUserRepresentation extends UserRepresentation
{
    public function __construct(UserRepresentation $user_representation, public string $uuid)
    {
        $this->id           = $user_representation->id;
        $this->uri          = $user_representation->uri;
        $this->user_url     = $user_representation->user_url;
        $this->real_name    = $user_representation->real_name;
        $this->display_name = $user_representation->display_name;
        $this->username     = $user_representation->username;
        $this->ldap_id      = $user_representation->ldap_id;
        $this->avatar_url   = $user_representation->avatar_url;
        $this->is_anonymous = $user_representation->is_anonymous;
        $this->has_avatar   = $user_representation->has_avatar;
        $this->email        = $user_representation->email;
        $this->status       = $user_representation->status;
    }
}
