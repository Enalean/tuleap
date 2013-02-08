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

require_once dirname(__FILE__) .'/../../docman/include/Docman_PermissionsItemManager.class.php';
/**
 * Class responsible to send requests to an indexation server
 */
class FullTextSearchDocmanActions extends FullTextSearchActions {

    protected $permissions_manager;

    public function __construct(FullTextSearch_IIndexDocuments $client, Docman_PermissionsItemManager $permissions_manager) {
        parent::__construct($client);
        $this->permissions_manager = $permissions_manager;
    }

    /**
     * Index a new document with permissions
     *
     * @param Docman_Item    $item    The docman item
     * @param Docman_Version $version The version to index
     */
    public function indexNewDocument(Docman_Item $item, Docman_Version $version) {
        $indexed_data = $this->getIndexedData($item, $version);
        $this->client->index($indexed_data, $item->getId());
    }

    /**
     * Index a new document with permissions
     *
     * @param Docman_Item    $item    The docman item
     * @param Docman_Version $version The version to index
     */
    public function indexNewVersion(Docman_Item $item, Docman_Version $version) {
        $update_data = $this->client->initializeSetterData();
        $update_data = $this->client->appendSetterData($update_data, 'file', $this->fileContentEncode($version->getPath()));
        $this->client->update($item->getid(), $update_data);
    }

    /**
     * Update title and description of a document
     *
     * @param Docman_Item $item The item
     */
    public function updateDocument(Docman_Item $item) {
        $update_data = $this->client->initializeSetterData();
        $update_data = $this->client->appendSetterData($update_data, 'title',       $item->getTitle());
        $update_data = $this->client->appendSetterData($update_data, 'description', $item->getDescription());
        $this->client->update($item->getid(), $update_data);
    }

    /**
     * Remove an indexed document
     *
     * @param Docman_Item $item The item to delete
     */
    public function delete(Docman_Item $item) {
        $this->client->delete($item->getId());
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

}
?>