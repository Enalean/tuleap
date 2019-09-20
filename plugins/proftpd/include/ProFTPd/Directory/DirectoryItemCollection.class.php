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

class DirectoryItemCollection
{

    /**
     * @var DirectoryItem[]
     */
    private $folders;

    /**
     * @var DirectoryItem[]
     */
    private $files;

    /**
     * User has access to directory
     * @var bool
     */
    private $is_forbidden = false;

    /**
     * @param DirectoryItem[] $folders
     * @param DirectoryItem[] $files
     */
    public function __construct($folders, $files)
    {
        $this->folders = $folders;
        $this->files = $files;
    }

    /**
     * @return DirectoryItem[]
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @return DirectoryItem[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    public function setAsForbidden()
    {
        $this->is_forbidden = true;
    }

    public function isForbidden()
    {
        return $this->is_forbidden;
    }
}
