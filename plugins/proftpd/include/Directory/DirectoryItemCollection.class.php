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

class Proftpd_Directory_DirectoryItemCollection {

    /**
     * @var Proftpd_Directory_DirectoryItem[]
     */
    private $folders;

    /**
     * @var Proftpd_Directory_DirectoryItem[]
     */
    private $files;

    /**
     * @param Proftpd_Directory_DirectoryItem[] $folders
     * @param Proftpd_Directory_DirectoryItem[] $files
     */
    public function __construct($folders, $files) {
        $this->folders = $folders;
        $this->files = $files;
    }

    /**
     * @return Proftpd_Directory_DirectoryItem[]
     */
    public function getFolders() {
        return $this->folders;
    }

    /**
     * @return Proftpd_Directory_DirectoryItem[]
     */
    public function getFiles() {
        return $this->files;
    }
}