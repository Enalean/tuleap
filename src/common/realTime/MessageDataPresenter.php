<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class MessageDataPresenter
{
    public $sender_user_id;
    public $sender_uuid;
    public $room_id;
    /**
     * @var MessageRightsPresenter
     */
    public $rights;
    public $cmd;
    public $data;

    public function __construct(
        $sender_user_id,
        $uuid,
        $room_id,
        MessageRightsPresenter $rights,
        $cmd,
        $data
    ) {
        $this->sender_user_id = intval($sender_user_id);
        $this->sender_uuid    = $uuid;
        $this->room_id        = $room_id;
        $this->data           = $data;
        $this->rights         = $rights;
        $this->cmd            = $cmd;
    }
}
