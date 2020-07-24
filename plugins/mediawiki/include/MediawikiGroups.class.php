<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

class MediawikiGroups
{
    private $added_removed = [
        'added'   => [],
        'removed' => [],
    ];

    private $added_index = [];

    private $original_groups = [];

    public function __construct(LegacyDataAccessResultInterface $original_groups)
    {
        foreach ($original_groups as $row) {
            $this->original_groups[$row['ug_group']] = true;
        }
    }

    public function getOriginalGroups()
    {
        return array_keys($this->original_groups);
    }

    public function add($group)
    {
        if (! isset($this->added_index[$group]) && ! isset($this->original_groups[$group])) {
            $this->added_removed['added'][] = $group;
        }
        $this->added_index[$group] = true;
    }

    public function getAddedRemoved()
    {
        $this->removeGroupsNotExplicitelyAdded();
        return $this->added_removed;
    }

    private function removeGroupsNotExplicitelyAdded()
    {
        foreach ($this->original_groups as $group => $nop) {
            if (! isset($this->added_index[$group])) {
                $this->added_removed['removed'][] = $group;
            }
        }
    }
}
