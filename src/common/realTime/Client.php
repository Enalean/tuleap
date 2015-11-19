<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\RealTime;

interface Client {

    /**
     * Method to send an Https request when
     * want to broadcast a message
     *
     * @param $user_id : Id of user
     * @param $hash    : Hash to distinguish client with same user id
     * @param $room_id : Room's id to broadcast message to this room
     * @param $rights  : To send at clients who have rights
     * @param $cmd     : Broadcast on event command
     * @param $data    : Data broadcasting
     */
    public function sendMessage($user_id, $hash, $room_id, $rights, $cmd, $data);
}