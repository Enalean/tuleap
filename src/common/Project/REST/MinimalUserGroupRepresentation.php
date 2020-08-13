<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use ProjectUGroup;
use Tuleap\User\UserGroup\NameTranslator;

/**
 * @psalm-immutable
 */
class MinimalUserGroupRepresentation
{

    public const ROUTE = 'user_groups';

    /**
     * @var string
     */
    public $id;

    /**
     * @var String
     */
    public $uri;

    /**
     * @var String
     */
    public $label;

    /**
     * @var String
     */
    public $users_uri;

    /**
     * @var String
     */
    public $short_name;
    /**
     * @var string
     */
    public $key;

    public function __construct(int $project_id, ProjectUGroup $ugroup)
    {
        $this->id         = UserGroupRepresentation::getRESTIdForProject($project_id, $ugroup->getId());
        $this->uri        = UserGroupRepresentation::ROUTE . '/' . $this->id;
        $this->label      = NameTranslator::getUserGroupDisplayName($ugroup->getName());
        $this->key        = $ugroup->getName();
        $this->users_uri  = UserGroupRepresentation::ROUTE . '/' . $this->id . '/users';
        $this->short_name = $ugroup->getNormalizedName();
    }
}
