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

use Tuleap\User\UserGroup\NameTranslator;

class DefaultFineGrainedPermission
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
    private $project_id;
    private $id;

    public function __construct($id, $project_id, $pattern, array $writers_ugroups, array $rewinders_ugroups)
    {
        $this->id                = $id;
        $this->project_id        = $project_id;
        $this->pattern           = $pattern;
        $this->writers_ugroups   = $writers_ugroups;
        $this->rewinders_ugroups = $rewinders_ugroups;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProjectId()
    {
        return $this->project_id;
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

    public function getWriterNames()
    {
        $ugroup_names = [];

        foreach ($this->writers_ugroups as $ugroup) {
            $ugroup_names[] = NameTranslator::getUserGroupDisplayName($ugroup->getName());
        }

        return implode(', ', $ugroup_names);
    }

    public function getRewinderNames()
    {
        $ugroup_names = [];

        foreach ($this->rewinders_ugroups as $ugroup) {
            $ugroup_names[] = NameTranslator::getUserGroupDisplayName($ugroup->getName());
        }

        return implode(', ', $ugroup_names);
    }

    public function getPatternWithoutPrefix()
    {
        $matches = [];
        preg_match("/^refs\/(?:heads|tags)\/(?P<pattern>.*)$/", $this->pattern, $matches);

        if (isset($matches['pattern'])) {
            return $matches['pattern'];
        }

        return $this->pattern;
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
