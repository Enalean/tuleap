<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use UserHelper;
use Tracker_FormElement_Field_List_Bind_UsersValue;

class UserRepresentation
{

    public const ROUTE = 'users';

    /**
     * @var int
     */
    public $id;

    /**
     * @var String
     */
    public $uri;

    /**
     * @var String
     */
    public $real_name;

    /**
     * @var String
     */
    public $username;

    public function build(Tracker_FormElement_Field_List_Bind_UsersValue $user)
    {
        $this->id        = $user->getId();
        $this->uri       = UserRepresentation::ROUTE . '/' . $this->id;
        $this->real_name = $user->getLabel();
        $this->username  = $user->getUsername();
    }
}
