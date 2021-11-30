<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\PermissionsPerGroup;

use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRepresentation;
use Tuleap\REST\JsonCast;

class FineGrainedPermissionRepresentation
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var PermissionPerGroupUGroupRepresentation[]
     */
    public $writers;
    /**
     * @var PermissionPerGroupUGroupRepresentation[]
     */
    public $rewinders;
    /**
     * @var string
     */
    public $branch;
    /**
     * @var string
     */
    public $tag;
    /**
     * @var array
     */
    private $all_ugroup_ids;

    public function __construct(
        $id,
        array $writers,
        array $rewinders,
        $branch,
        $tag,
        array $all_ugroup_ids,
    ) {
        $this->id             = JsonCast::toInt($id);
        $this->writers        = $writers;
        $this->rewinders      = $rewinders;
        $this->branch         = $branch;
        $this->tag            = $tag;
        $this->all_ugroup_ids = $all_ugroup_ids;
    }

    /**
     * @return PermissionPerGroupUGroupRepresentation[]
     */
    public function getWriters()
    {
        return $this->writers;
    }

    /**
     * @return PermissionPerGroupUGroupRepresentation[]
     */
    public function getRewinders()
    {
        return $this->rewinders;
    }

    /**
     * @return array
     */
    public function getAllUGroupIds()
    {
        return $this->all_ugroup_ids;
    }
}
