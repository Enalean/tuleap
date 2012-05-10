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


class FullTextSearchActions {
    
    protected $client;
    
    public function __construct($client) {
        $this->client = $client;
    }
    
    public function indexNewDocument($params) {
        $item_id = $params['item']->getId();
        $group_id= $params['item']->getGroupId();
        $user    = $params['user'];
        
        $permissions = $this->permissions_values($params['item']->getPermissions());
        
        $indexed_datas = array(
    		'title'       => $params['item']->getTitle(),
    		'description' => $params['item']->getDescription(),
            'file'        => $this->file_content_encode($params['version']->getPath()), 
            'permissions' => array($group_id => $permissions)
        );
        
        $this->client->index($indexed_datas, $item_id);
    }
    
    public function delete($params) {
        $this->client->delete($params['item']->getId());
    }
    
    protected function file_content_encode($file_name) {
        return base64_encode(file_get_contents($file_name));
    }
    
    protected function permissions_values($permissions) {
        $permissions_values = array();
        foreach($permissions as $permission) {
            $permissions_values = array_merge($permissions_values, array_values($permission));
        }
        return $permissions_values;
    }
}
?>