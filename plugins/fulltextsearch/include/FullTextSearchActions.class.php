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

require_once 'FullTextSearch/ISearchAndIndexDocuments.class.php';
require_once dirname(__FILE__) .'/../../docman/include/Docman_PermissionsManager.class.php';
/**
 * Class responsible to send requests to an indexation server 
 */
class FullTextSearchActions {

    /**
     * @var FullTextSearch_ISearchAndIndexDocuments 
     */
    protected $client;
    
    public function __construct(FullTextSearch_ISearchAndIndexDocuments $client) {
        $this->client = $client;
    }

    /**
     * Wrapper for tests
     *
     * *@return Docman_PermissionsManager
     */
    protected function getDocmanPermissionsManager($group_id) {
        return Docman_PermissionsManager::instance($group_id);
    }

    /**
     * Index a new document with permissions
     * 
     * @param array $params parameters of the docman event
     */
    public function indexNewDocument($params) {
        $item_id     = $params['item']->getId();
        $group_id    = $params['item']->getGroupId();
        $user        = $params['user'];
        $permissions = $this->getDocmanPermissionsManager($group_id)->exportPermissions($params['item']);
        $indexed_datas = array(
            'id'          => $item_id,
            'title'       => $params['item']->getTitle(),
            'description' => $params['item']->getDescription(),
            'file'        => $this->fileContentEncode($params['version']->getPath()),
            'permissions' => array($group_id => $permissions)
        );
        
        $this->client->index($indexed_datas);
    }
    
    /**
     * Remove an indexed document
     * 
     * @param array $params
     */
    public function delete($params) {
        $this->client->delete($params['item']->getId());
    }
    
    /**
     * Get file contents and encode them with base64
     * 
     * @param string $file_name
     * @return string
     */
    private function fileContentEncode($file_name) {
        if (is_file($file_name)) {
            return base64_encode(file_get_contents($file_name));
        }
        return '';
    }
    
}
?>
