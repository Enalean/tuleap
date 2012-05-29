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
require_once dirname(__FILE__) .'/../../docman/include/Docman_PermissionsItemManager.class.php';
/**
 * Class responsible to send requests to an indexation server
 */
class FullTextSearchActions {

    /**
     * @var FullTextSearch_ISearchAndIndexDocuments
     */
    protected $client;
    protected $permissions_manager;

    public function __construct(FullTextSearch_ISearchAndIndexDocuments $client, Docman_PermissionsItemManager $permissions_manager) {
        $this->client              = $client;
        $this->permissions_manager = $permissions_manager;
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
        $permissions = $this->permissions_manager->exportPermissions($params['item']);
        $indexed_datas = array(
            'id'          => $item_id,
            'group_id'    => $group_id,
            'title'       => $params['item']->getTitle(),
            'description' => $params['item']->getDescription(),
            'permissions' => $permissions,
            'file'        => $this->fileContentEncode($params['version']->getPath())
        );

        $this->client->index($indexed_datas, $item_id);
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
