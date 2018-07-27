<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

require_once __DIR__.'/../../../docman/include/Docman_File.class.php';

Mock::generate('Docman_File');

function aDocman_File() {
    return new Docman_File_Builder();
}

class Docman_File_Builder {
    public $item_id     = 'item_id';
    public $group_id    = 'group_id';
    public $title       = 'title';
    public $description = 'description';
    public $owner_id    = 'owner_id';
    public $permissions = array();
    
    public function build() {
        $docman_file = mock('Docman_File');
        stub($docman_file)->getId()->returns($this->item_id);
        stub($docman_file)->getGroupId()->returns($this->group_id);
        stub($docman_file)->getTitle()->returns($this->title);
        stub($docman_file)->getDescription()->returns($this->description);
        stub($docman_file)->getOwnerId()->returns($this->owner_id);
        return $docman_file;
    }
    
    public function withId($item_id) {
        $this->item_id = $item_id;
        return $this;
    }
    
    public function withGroupId($group_id) {
        $this->group_id = $group_id;
        return $this;
    }
    
    public function withOwnerId($owner_id) {
        $this->owner_id = $owner_id;
        return $this;
    }

    public function withTitle($title) {
        $this->title = $title;
        return $this;
    }
    
    public function withDescription($description) {
        $this->description = $description;
        return $this;
    }
    
    public function withPermissions($permissions) {
        $this->permissions = $permissions;
        return $this;
    }
}
?>
