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
 * This class is a representation of an item into a SFTP directory
 */
class DirectoryItem
{

    /** @var String */
    private $name;

    /** @var String */
    private $type;

    /** @var int */
    private $size;

    /** @var int */
    private $last_modified_date;

    public function __construct($name, $type, $size, $last_modified_date)
    {
        $this->name               = $name;
        $this->type               = $type;
        $this->size               = $size;
        $this->last_modified_date = $last_modified_date;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getLastModifiedDate()
    {
        if (! $this->last_modified_date) {
            return '';
        }
        return date('Y M d H:i', $this->last_modified_date);
    }
}
