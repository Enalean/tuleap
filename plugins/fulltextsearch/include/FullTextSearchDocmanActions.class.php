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

    /** @var ElasticSearch_1_2_RequestDataFactory */
    private $request_data_factory;

    /** @var BackendLogger */
    private $logger;

    public function __construct(
        FullTextSearch_IIndexDocuments $client,
        ElasticSearch_1_2_RequestDataFactory $request_data_factory,
        BackendLogger $logger
    ) {
        parent::__construct($client);
        $this->request_data_factory = $request_data_factory;
        $this->logger               = $logger;
    }

    public function checkProjectMappingExists($project_id) {
        $this->logger->debug('ElasticSearch: get the mapping for project #' . $project_id);

        return count($this->client->getProjectMapping($project_id)) > 0;
    }

    public function initializeProjetMapping($project_id) {
        $this->logger->debug('ElasticSearch: initialize the mapping for project #' . $project_id);

        $this->client->defineProjectMapping(
            $project_id,
            $this->request_data_factory->getPUTMappingData($project_id)
        );
    }

    /**
     * Index a new document with permissions
     *
     * @param Docman_Item    $item    The docman item
     * @param Docman_Version $version The version to index
     */
    public function indexNewDocument(Docman_Item $item, Docman_Version $version) {
        $this->logger->debug('ElasticSearch: index new document #' . $item->getId());

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
        $this->logger->debug('ElasticSearch: index new  version (# ' . $version->getId() .
            ' for document #' . $item->getId()
        );

        $update_data = $this->request_data_factory->initializeSetterData();
        $update_data = $this->request_data_factory->appendSetterData($update_data, 'file', $this->fileContentEncode($version->getPath()));

        $this->client->update($item, $update_data);
    }

    /**
     * Update title, description and custom textual metadata of a document
     *
     * @param Docman_Item $item The item
     */
    public function updateDocument(Docman_Item $item) {
        $this->logger->debug('ElasticSearch: update metadata of document #' . $item->getId());

        $update_data = $this->request_data_factory->initializeSetterData();
        $update_data = $this->request_data_factory->appendSetterData($update_data, 'title',       $item->getTitle());
        $update_data = $this->request_data_factory->appendSetterData($update_data, 'description', $item->getDescription());

        $update_data = $this->request_data_factory->updateCustomTextualMetadata($item, $update_data);
        $update_data = $this->updateCustomDateMetadata($item, $update_data);

        $this->client->update($item, $update_data);
    }

    /**
     * Index the new permissions of a document
     *
     * @param Docman_Item the document
     */
    public function updatePermissions(Docman_Item $item) {
        $this->logger->debug('ElasticSearch: update permissions of document #' . $item->getId());

        $update_data = $this->request_data_factory->initializeSetterData();
        $update_data = $this->request_data_factory->appendSetterData(
            $update_data,
            'permissions',
            $this->request_data_factory->getCurrentPermissions($item)
        );

        $this->client->update($item, $update_data);
    }

    /**
     * Remove an indexed document
     *
     * @param Docman_Item $item The item to delete
     */
    public function delete(Docman_Item $item) {
        $this->logger->debug('ElasticSearch: delete document #' . $item->getId());

        $this->client->delete($item);
    }

    private function getIndexedData(Docman_Item $item, Docman_Version $version) {
        return $this->request_data_factory->getIndexedDataForItemVersion($item, $version) +
            $this->request_data_factory->getCustomTextualMetadataValue($item) +
            $this->getCustomDateMetadata($item);
    }

    /**
     * Get the user defined item date metadata
     *
     * @param Docman_Item $item The item indexed
     *
     * @return array
     */

    private function getCustomDateMetadata(Docman_Item $item) {
        $this->updateMappingWithNewDateMetadata($item);

        return $this->request_data_factory->getCustomDateMetadataValues($item);
    }


    private function updateCustomDateMetadata(Docman_Item $item, array $update_data) {
        $this->updateMappingWithNewDateMetadata($item);

        return $this->request_data_factory->updateCustomDateMetadata($item, $update_data);
    }

    private function updateMappingWithNewDateMetadata(Docman_Item $item) {
        $mapping_data = $this->request_data_factory->getPUTDateMappingMetadata(
            $item,
            $this->client->getProjectMapping($item->getGroupId())
        );

        if (! $this->mappingNeedsToBoUpdated($item, $mapping_data)) {
            return;
        }

        $this->logger->debug('ElasticSearch: update mapping of project #' . $item->getGroupId() .
            'with new custom date metadata');

        $this->client->defineProjectMapping(
            $item->getGroupId(),
            $mapping_data
        );
    }

    private function mappingNeedsToBoUpdated(Docman_Item $item, array $mapping_data) {
        return $mapping_data[$item->getGroupId()][ElasticSearch_1_2_RequestDataFactory::MAPPING_PROPERTIES_KEY]
            !== array();
    }
}