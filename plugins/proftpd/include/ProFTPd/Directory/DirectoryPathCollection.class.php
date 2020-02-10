<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\ProFTPd\Directory;

/**
 * I represent a collection of DirectoryPathPart
 */
class DirectoryPathCollection
{

    private $collection = array();

    public function add(DirectoryPathPart $path_part)
    {
        $this->collection[] = $path_part;
    }

    public function count()
    {
        return count($this->collection);
    }

    /**
     * @return DirectoryPathPart | null
     */
    public function last()
    {
        return end($this->collection);
    }

    /**
     * @return DirectoryPathPart[]
     */
    public function parent_directory_parts()
    {
        if (count($this->collection) > 1) {
            $parent_directories = $this->collection;
            array_pop($parent_directories);

            return $parent_directories;
        }

        return array();
    }
}
