<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Permissions;

class FineGrainedPermissionRepresentation
{

    /**
     * @var array
     */
    private $rewinders_ugroup_ids;

    /**
     * @var array
     */
    private $writers_ugroup_ids;
    private $pattern;
    private $repository_id;
    private $id;

    public function __construct($id, $repository_id, $pattern, array $writers_ugroup_ids, array $rewinders_ugroup_ids)
    {
        $this->id                   = $id;
        $this->repository_id        = $repository_id;
        $this->writers_ugroup_ids   = $writers_ugroup_ids;
        $this->pattern              = $pattern;
        $this->rewinders_ugroup_ids = $rewinders_ugroup_ids;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRepositoryId()
    {
        return $this->repository_id;
    }

    public function getPattern()
    {
        return $this->pattern;
    }

    public function getWritersUgroupIds()
    {
        return $this->writers_ugroup_ids;
    }

    public function getRewindersUgroupIds()
    {
        return $this->rewinders_ugroup_ids;
    }

    public function getDisplayablePattern()
    {
        $matches = array();
        preg_match("/^refs\/(?:heads|tags)\/(?P<pattern>.*)$/", $this->pattern, $matches);

        return $matches['pattern'];
    }
}
