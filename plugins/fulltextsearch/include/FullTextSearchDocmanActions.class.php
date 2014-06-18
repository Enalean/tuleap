<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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

/**
 * Class responsible to send requests to an indexation server
 */
class FullTextSearchDocmanActions extends FullTextSearchActions {

    /** @var Docman_PermissionsItemManager */
    protected $permissions_manager;

    /** @var Docman_MetadataFactory */
    private $metadata_factory;

    /** @var ElasticSearch_1_2_RequestDataFactory */
    private $request_data_factory;

    public function __construct(
        FullTextSearch_IIndexDocuments $client,
        Docman_PermissionsItemManager $permissions_manager,
        Docman_MetadataFactory $metadata_factory,
        ElasticSearch_1_2_RequestDataFactory $request_data_factory
    ) {
        parent::__construct($client);
        $this->permissions_manager  = $permissions_manager;
        $this->metadata_factory     = $metadata_factory;
        $this->request_data_factory = $request_data_factory;
    }

    public function checkProjectMappingExists($project_id) {
        return count($this->client->getProjectMapping($project_id)) > 0;
    }

    public function initializeProjetMapping($project_id) {
        $this->client->initializeProjectMapping($project_id, $this->getMappingData($project_id));
    }

    private function getMappingData($project_id) {
        $this->metadata_factory->setRealGroupId($project_id);
        $hardcoded_metadata = $this->metadata_factory->getHardCodedMetadataList();

        return $this->request_data_factory->getPUTMappingData($hardcoded_metadata, $project_id);
    }

    /**
     * Index a new document with permissions
     *
     * @param Docman_Item    $item    The docman item
     * @param Docman_Version $version The version to index
     */
    public function indexNewDocument(Docman_Item $item, Docman_Version $version) {
        $indexed_data = $this->getIndexedData($item, $version);
        $this->client->index($indexed_data, $item);
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
        $this->client->update($item, $update_data);
    }

    /**
     * Update title, description and custom textual metadata of a document
     *
     * @param Docman_Item $item The item
     */
    public function updateDocument(Docman_Item $item) {
        $update_data = $this->client->initializeSetterData();
        $update_data = $this->client->appendSetterData($update_data, 'title',       $item->getTitle());
        $update_data = $this->client->appendSetterData($update_data, 'description', $item->getDescription());
        $update_data = $this->updateCustomTextualMetadata($item, $update_data);

        $this->client->update($item, $update_data);
    }

    /**
     * Index the new permissions of a document
     *
     * @param Docman_Item the document
     */
    public function updatePermissions(Docman_Item $item) {
        $update_data = $this->client->initializeSetterData();
        $update_data = $this->client->appendSetterData($update_data, 'permissions', $this->permissions_manager->exportPermissions($item));
        $this->client->update($item, $update_data);
    }

    /**
     * Remove an indexed document
     *
     * @param Docman_Item $item The item to delete
     */
    public function delete(Docman_Item $item) {
        $this->client->delete($item);
    }

    private function getIndexedData(Docman_Item $item, Docman_Version $version) {
        return array(
            'id'          => $item->getId(),
            'group_id'    => $item->getGroupId(),
            'title'       => $item->getTitle(),
            'description' => $item->getDescription(),
            'permissions' => $this->permissions_manager->exportPermissions($item),
            'file'        => $this->fileContentEncode($version->getPath())
        ) + $this->getCustomTextualMetadata($item);
    }

    /**
     * Get the user defined item textual metadata
     *
     * @param Docman_Item $item The item indexed
     *
     * @return array
     */
    private function getCustomTextualMetadata(Docman_Item $item) {
        $this->metadata_factory->setRealGroupId($item->groupId);
        $item_textual_metadatas = $this->metadata_factory->getRealMetadataList(
            false,
            array(
                PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
                PLUGIN_DOCMAN_METADATA_TYPE_STRING
            )
        );

        $custom_metadata = array();
        foreach ($item_textual_metadatas as $item_metadata) {
            $custom_metadata['property_' . $item_metadata->getId()] = $this->metadata_factory->getMetadataValue($item, $item_metadata);
        }

        return $custom_metadata;
    }

    private function updateCustomTextualMetadata(Docman_Item $item, array $update_data) {
        $custom_textual_metadata = $this->getCustomTextualMetadata($item);
        foreach ($custom_textual_metadata as $metadata_name => $metadata_value) {
            $update_data = $this->client->appendSetterData($update_data, $metadata_name, $metadata_value);
        }

        return $update_data;
    }
}
