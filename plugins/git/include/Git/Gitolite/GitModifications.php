<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

class Git_Gitolite_GitModifications
{

    private $files_to_add;

    private $files_to_move;

    private $files_to_remove;

    public function __construct()
    {
        $this->files_to_add    = array();
        $this->files_to_move   = array();
        $this->files_to_remove = array();
    }

    public function add($file)
    {
        $this->files_to_add[] = $file;
    }

    public function move($old_file, $new_file)
    {
        $this->files_to_move[$old_file] = $new_file;
    }

    public function remove($file)
    {
        $this->files_to_remove[] = $file;
    }

    public function toAdd()
    {
        return $this->files_to_add;
    }

    public function toMove()
    {
        return $this->files_to_move;
    }

    public function toRemove()
    {
        return $this->files_to_remove;
    }
}
