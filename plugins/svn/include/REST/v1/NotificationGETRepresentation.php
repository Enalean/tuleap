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

use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-immutable
 */
class NotificationGETRepresentation
{
    /**
     * @var UserGroupRepresentation[] user groups representations {@type \Tuleap\Project\REST\UserGroupRepresentation} {@required false}
     */
    public $user_groups;

    /**
     * @var MinimalUserRepresentation[] minimal users representations {@type \Tuleap\User\REST\MinimalUserRepresentation} {@required false}
     */
    public $users;
    /**
     * @var string[] emails {@type email} {@required true} {@min 1}
     */
    public $emails;
    /**
     * @var string path {@required true}
     */
    public $path;

    public function __construct(array $notification, string $path)
    {
        $this->path        = $path;
        $this->user_groups = $notification['ugroups'];
        $this->users       = $notification['users'];
        $this->emails      = $notification['emails'];
    }
}
