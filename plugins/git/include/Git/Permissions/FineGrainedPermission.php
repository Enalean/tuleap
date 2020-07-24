<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

class FineGrainedPermission implements Permission
{

    /**
     * @var array
     */
    private $rewinders_ugroups;

    /**
     * @var array
     */
    private $writers_ugroups;
    private $pattern;
    private $repository_id;
    private $id;

    public function __construct($id, $repository_id, $pattern, array $writers_ugroups, array $rewinders_ugroups)
    {
        $this->id                = $id;
        $this->repository_id     = $repository_id;
        $this->pattern           = $pattern;
        $this->writers_ugroups   = $writers_ugroups;
        $this->rewinders_ugroups = $rewinders_ugroups;
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

    public function getWritersUgroup()
    {
        return $this->writers_ugroups;
    }

    public function getRewindersUgroup()
    {
        return $this->rewinders_ugroups;
    }

    public function getPatternWithoutPrefix()
    {
        $matches = [];
        preg_match("/^refs\/(?:heads|tags)\/(?P<pattern>.*)$/", $this->pattern, $matches);

        return $matches['pattern'];
    }

    public function setWriters(array $writers)
    {
        $this->writers_ugroups = $writers;
    }

    public function setRewinders(array $rewinders)
    {
        $this->rewinders_ugroups = $rewinders;
    }
}
