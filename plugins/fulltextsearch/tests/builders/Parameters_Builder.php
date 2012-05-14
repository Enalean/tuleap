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

require_once dirname(__FILE__).'/Docman_File_Builder.php';
require_once DOCMAN_INCLUDE_PATH.'/Docman_Version.class.php';
require_once 'common/user/User.class.php';


Mock::generate('Docman_Version');
Mock::generate('User');

function aSetOfParameters() {
    return new Parameters_Builder();
}

class Parameters_Builder {
    public $item;
    public $version;
    public $user;
    
    public function __construct() {
        $this->item    = aDocman_File();
        $this->version = new MockDocman_Version();
        $this->user    = new MockUser();
    }
    
    public function build() {
        return array(
                'item'    => $this->item->build(),
                'version' => $this->version,
                'user'    => $this->user
        );
    }
    
    public function getClientIndexParameters() {
        $expected_permissions = $this->item->getExpectedPermissions($this->item->permissions);
        $expected_datas       = array(
                'title'       => $this->item->title,
                'description' => $this->item->description,
                'file'        => $this->version->getPath(),
                'permissions' => array($this->item->group_id => $expected_permissions)
        );
        return array($expected_datas, $this->item->item_id);
    }
}
?>