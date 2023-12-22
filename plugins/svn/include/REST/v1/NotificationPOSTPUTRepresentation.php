<?php
/**
 *  Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\REST\v1;

/**
 * @psalm-immutable
 */
class NotificationPOSTPUTRepresentation
{
    /**
     * @var string[] user groups ids {@type string} {@required true} {@min 0}
     */
    public $user_groups;
    /**
     * @var int[] users ids {@type int} {@required true} {@min 0}
     */
    public $users;
    /**
     * @var string[] emails {@type string} {@required true} {@min 0}
     */
    public $emails;
    /**
     * @var string path {@required true}
     */
    public $path;

    /**
     * @psalm-param array{ugroups: list<string>, users: list<int>, emails: list<string>} $notification
     */
    public function __construct(array $notification, string $path)
    {
        $this->path        = $path;
        $this->user_groups = $notification['ugroups'];
        $this->users       = $notification['users'];
        $this->emails      = $notification['emails'];
    }
}
