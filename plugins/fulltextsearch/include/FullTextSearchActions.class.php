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

require_once 'FullTextSearch/IIndexDocuments.class.php';
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

    public function __construct(FullTextSearch_IIndexDocuments $client, Docman_PermissionsItemManager $permissions_manager) {
        $this->client              = $client;
        $this->permissions_manager = $permissions_manager;
    }


    /**
     * Index a new document with permissions
     *
     * @param array $params parameters of the docman event
     */
    public function indexNewDocument($params) {
        $indexed_data = $this->getIndexedData($params['item'], $params['version']);
        $this->client->index($indexed_data, $params['item']->getId());
    }

    /**
     * Update title and description if they've changed
     * $params are kept as array to be compliant with others events,
     * but we merely need event objects
     *
     * @param array $params
     */
    public function updateDocument($params) {
        $item         = $params['item'];
        $new_data     = $params['new'];
        $update_data  = array('script'=>'', 'params'=> array());
        $updated      = false;
        if ($this->titleUpdated($new_data['title'], $item)) {
            $update_data = $this->client->buildSetterData($update_data, 'title', $new_data['title']);
            $updated     = true;
        }
        if ($this->descriptionUpdated($new_data, $item)) {
            $update_data = $this->client->buildSetterData($update_data, 'description', $new_data['description']);
            $updated     = true;
        }
        if ($updated) {
            $this->client->update($item->getid(), $update_data);
        }
    }

    private function titleUpdated($data, Docman_Item $item) {
        return isset($data['title']) && $data['title'] != $item->getTitle();
    }

    private function descriptionUpdated($data, Docman_Item $item) {
        return isset($data['description']) && $data['description'] != $item->getDescription();
    }

    private function getIndexedData(Docman_Item $item, Docman_Version $version) {
        return array(
            'id'          => $item->getId(),
            'group_id'    => $item->getGroupId(),
            'title'       => $item->getTitle(),
            'description' => $item->getDescription(),
            'permissions' => $this->permissions_manager->exportPermissions($item),
            'file'        => $this->fileContentEncode($version->getPath())
        );
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
