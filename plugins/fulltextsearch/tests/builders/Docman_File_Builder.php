<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../Constants.php';
require_once DOCMAN_INCLUDE_PATH.'/Docman_File.class.php';

Mock::generate('Docman_File');

function aDocman_File() {
    return new Docman_File_Builder();
}

class Docman_File_Builder {
    public $item_id     = 'item_id';
    public $group_id    = 'group_id';
    public $title       = 'title';
    public $description = 'description';
    public $permissions = array();
    
    /**
     * identical as FullTextSearchActions::permissions_values
     */
    public static function getExpectedPermissions($permissions) {
        $permissions_values = array();
        foreach($permissions as $permission) {
            $permissions_values = array_merge($permissions_values, array_values($permission));
        }
        return $permissions_values;
    }
    
    public function build() {
        $docman_file = new MockDocman_File();
        $docman_file->setReturnValue('getId', $this->item_id);
        $docman_file->setReturnValue('getGroupId', $this->group_id);
        $docman_file->setReturnValue('getTitle', $this->title);
        $docman_file->setReturnValue('getDescription', $this->description);
        $expected_permissions = self::getExpectedPermissions($this->permissions);
        $docman_file->setReturnValue('getPermissions', $expected_permissions);
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