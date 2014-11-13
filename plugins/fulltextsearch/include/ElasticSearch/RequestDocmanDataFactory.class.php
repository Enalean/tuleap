<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * I build data for ElasticSearch 1.2 requests
 */

class ElasticSearch_1_2_RequestDocmanDataFactory {

    const EXCLUDED_HARDCODED_METADATA = 'status';
    const MAPPING_PROPERTIES_KEY      = 'properties';
    const MAPPING_MAPPINGS_KEY        = 'mappings';
    const MAPPING_DOCMAN_ROOT_KEY     = '';

    const CUSTOM_PROPERTY_PREFIX      = 'property';

    const ELASTICSEARCH_STRING_TYPE   = 'string';
    const ELASTICSEARCH_DATE_TYPE     = 'date';
    const UNPARSABLE_TYPE             =  null;

    /** @var Docman_MetadataFactory */
    private $metadata_factory;

    /** @var Docman_PermissionsItemManager */
    private $permissions_manager;

    /** @var Docman_ApprovalTableFactoriesFactory */
    private $approval_table_factory;

    public function __construct(
        Docman_MetadataFactory $metadata_factory,
        Docman_PermissionsItemManager $permissions_manager,
        Docman_ApprovalTableFactoriesFactory $approval_table_factory
    ) {
        $this->metadata_factory       = $metadata_factory;
        $this->permissions_manager    = $permissions_manager;
        $this->approval_table_factory = $approval_table_factory;
    }

    /**
     * Builds the data needed for
     * the very first PUT /docman/:project_id/_mapping
     *
     * @param type  $project_id
     *
     * @return array
     */
    public function getPUTMappingData($project_id) {
        $mapping_data                   = $this->initializePUTMappingData($project_id);
        $hardcoded_metadata_for_mapping = array();

        foreach ($this->getHardcodedMetadata($project_id) as $metadata) {
            if ($this->getElasticSearchMappingType($metadata->getType()) === self::ELASTICSEARCH_DATE_TYPE) {
                $hardcoded_metadata_for_mapping[$metadata->getLabel()] = array(
                    'type'   => self::ELASTICSEARCH_DATE_TYPE,
                    'format' => 'date_time_no_millis'
                );

            } else {
                $hardcoded_metadata_for_mapping[$metadata->getLabel()] = array(
                    'type' => $this->getElasticSearchMappingType($metadata->getType())
                );
            }
        }

        $this->removeUnparsableMetadata($hardcoded_metadata_for_mapping);
        $this->addStandardFileMetadata($hardcoded_metadata_for_mapping);
        $this->addStandardContentMetadata($hardcoded_metadata_for_mapping);
        $this->addStandardPermissionsMetadata($hardcoded_metadata_for_mapping);
        $this->addApprovalTableMetadata($hardcoded_metadata_for_mapping);

        $mapping_data[$project_id][self::MAPPING_PROPERTIES_KEY] = $hardcoded_metadata_for_mapping;

        return $mapping_data;
    }

    private function addApprovalTableMetadata(array &$hardcoded_metadata_for_mapping) {
        $hardcoded_metadata_for_mapping['approval_table_comments'] = array(
            'properties' => array(
                'user_id' => array(
                    'type' => 'integer',
                ),
                'date_added' => array(
                    'type'   => 'date',
                    'format' => 'date_time_no_millis',
                ),
                'comment' => array(
                    'type' => 'string',
                ),
            )
        );
    }

    /**
     * Builds the custom date data
     * needed for PUT /docman/:project_id/:document_id (creation and update)
     *
     * @param Docman_Item $item
     *
     * @return array
     */

    public function getCustomDateMetadataValues(Docman_Item $item) {
        $custom_metadata = array();

        foreach ($this->getCustomDateMetadata($item) as $item_metadata) {
            $custom_metadata[$this->getCustomPropertyName($item_metadata)] = date(
                'c',
                (int) $this->metadata_factory->getMetadataValue($item, $item_metadata)
            );
        }

        return $custom_metadata;
    }

    /**
     * Builds the data needed for
     * the PUT /docman/:project_id/_mapping
     * when adding new date fields
     *
     * @param Docman_Item $item
     * @param array       $mapping
     *
     * @return array
     */
    public function getPUTDateMappingMetadata(Docman_Item $item, array $mapping) {
        $mapping_data = $this->initializePUTMappingData($item->getGroupId());

        $custom_metadata_to_define = array();
        foreach ($this->getCustomDateMetadata($item) as $item_metadata) {
            if (! $this->dateMetadataIsInMapping($mapping, $item, $item_metadata)) {
                $custom_metadata_to_define[$this->getCustomPropertyName($item_metadata)] = array(
                    'type' => 'date',
                    'format' => 'date_time_no_millis'
                );
            }
        }

        $mapping_data[$item->getGroupId()][self::MAPPING_PROPERTIES_KEY] = $custom_metadata_to_define;

        return $mapping_data;
    }

    /**
     * Get the custom text metadata values for item
     *
     * @param Docman_Item $item
     *
     * @return array
     */
    public function getCustomTextualMetadataValue(Docman_Item $item) {
        $custom_metadata = array();
        foreach ($this->getCustomTextualMetadata($item) as $item_metadata) {
            $custom_metadata[$this->getCustomPropertyName($item_metadata)] =
                $this->metadata_factory->getMetadataValue($item, $item_metadata);
        }

        return $custom_metadata;
    }

    /**
     * Builds the indexed data for first indexation
     *
     * @param Docman_Item    $item
     * @param Docman_Version $version
     *
     * @return array
     */
    public function getIndexedDataForItemVersion(Docman_Item $item) {
        $hardcoded_metadata = array(
            'id'                      => $item->getId(),
            'group_id'                => $item->getGroupId(),
            'title'                   => $item->getTitle(),
            'description'             => $item->getDescription(),
            'create_date'             => date('c', $item->getCreateDate()),
            'update_date'             => date('c', $item->getUpdateDate()),
            'permissions'             => $this->permissions_manager->exportPermissions($item),
            'approval_table_comments' => $this->getDocumentApprovalTableComments($item),
            'owner'                   => $item->getOwnerId()
        );

        if ($item->getObsolescenceDate()) {
            $hardcoded_metadata['obsolescence_date'] = date('c', $item->getObsolescenceDate());
        }

        return $hardcoded_metadata;
    }

    public function getDocumentApprovalTableComments(Docman_Item $item) {
        $comments               = array();
        $approval_table_factory = $this->approval_table_factory->getSpecificFactoryFromItem($item);
        if (! $approval_table_factory) {
            return $comments;
        }

        $table = $approval_table_factory->getTable();
        if (! $table) {
            return $comments;
        }

        $review_factory = $this->approval_table_factory->getReviewerFactory($table, $item);
        $reviews        = $review_factory->getReviewerListForLatestVersion();

        foreach ($reviews as $review) {
            /* @var $review Docman_ApprovalReviewer */
            $comments[] = array(
                'user_id'    => $review->getId(),
                'date_added' => $review->getReviewDate(),
                'comment'    => $review->getComment()
            );
        }

        return $comments;
    }

    public function getCurrentPermissions(Docman_Item $item) {
        return $this->permissions_manager->exportPermissions($item);
    }

    public function setUpdatedData(array &$current_data, $name, $value) {
        $current_data[$name] = $value;
    }

    public function updateFile(array &$update_data, $file_path) {
        $update_data['file'] = $this->fileContentEncode($file_path);
    }

    public function updateContent(array &$update_data, $content) {
        $update_data['content'] = $content;
    }

    public function updateCustomTextualMetadata(Docman_Item $item, array $update_data) {
        $custom_textual_metadata = $this->getCustomTextualMetadataValue($item);

        foreach ($custom_textual_metadata as $metadata_name => $metadata_value) {
            $this->setUpdatedData($update_data, $metadata_name, $metadata_value);
        }

        return $update_data;
    }

    public function updateCustomDateMetadata(Docman_Item $item, array $update_data) {
        $custom_date_metadata = $this->getCustomDateMetadataValues($item);

        foreach ($custom_date_metadata as $metadata_name => $metadata_value) {
            $this->setUpdatedData(
                $update_data,
                $metadata_name,
                $metadata_value
            );
        }

        return $update_data;
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

    private function getHardcodedMetadata($project_id) {
        $this->metadata_factory->setRealGroupId($project_id);

        return $this->metadata_factory->getHardCodedMetadataList();
    }

    private function initializePUTMappingData($project_id) {
        return array(
            (string) $project_id => array(
                self::MAPPING_PROPERTIES_KEY => array()
            )
        );
    }

    private function removeUnparsableMetadata(array &$hardcoded_metadata_for_mapping) {
        $hardcoded_metadata_for_mapping = array_filter(
            $hardcoded_metadata_for_mapping,
            array($this, 'checkIfHardcodedMetadataIsParsable')
        );
    }

    private function checkIfHardcodedMetadataIsParsable($hardcoded_metadata) {
        return $hardcoded_metadata['type'] !== self::UNPARSABLE_TYPE;
    }

    private function addStandardFileMetadata(array &$hardcoded_metadata_for_mapping) {
        $hardcoded_metadata_for_mapping['file'] = array(
            'type' => 'attachment',
            'fields' => array(
                'title' => array('store' => 'yes'),
                'file'  => array(
                    'term_vector' => 'with_positions_offsets',
                    'store' => 'yes'
                )
            )
        );
    }

    private function addStandardContentMetadata(array &$hardcoded_metadata_for_mapping) {
        $hardcoded_metadata_for_mapping['content'] = array(
            'type' => 'string'
        );
    }

    private function addStandardPermissionsMetadata(array &$hardcoded_metadata_for_mapping) {
        $hardcoded_metadata_for_mapping['permissions'] = array(
            'type'  => 'string',
            'index' => 'not_analyzed'
        );
    }

    private function getElasticSearchMappingType($type) {
        $types = array(
            PLUGIN_DOCMAN_METADATA_TYPE_STRING => self::ELASTICSEARCH_STRING_TYPE,
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT   => self::ELASTICSEARCH_STRING_TYPE,
            PLUGIN_DOCMAN_METADATA_TYPE_DATE   => self::ELASTICSEARCH_DATE_TYPE,
            PLUGIN_DOCMAN_METADATA_TYPE_LIST   => self::UNPARSABLE_TYPE
        );

        return $types[$type];
    }

    private function dateMetadataIsInMapping(
        array $mapping,
        Docman_Item $item,
        Docman_Metadata $item_metadata
    ) {

        return $this->mappingIsWellFormed($item, $mapping) && array_key_exists(
            $this->getCustomPropertyName($item_metadata),
            $mapping[self::MAPPING_DOCMAN_ROOT_KEY]
                    [self::MAPPING_MAPPINGS_KEY]
                    [$item->getGroupId()]
                    [self::MAPPING_PROPERTIES_KEY]
        );
    }

    private function mappingIsWellFormed(Docman_Item $item, array $mapping) {
        return isset($mapping[self::MAPPING_DOCMAN_ROOT_KEY]) &&
            isset($mapping[self::MAPPING_DOCMAN_ROOT_KEY][self::MAPPING_MAPPINGS_KEY]) &&
            isset($mapping[self::MAPPING_DOCMAN_ROOT_KEY]
                          [self::MAPPING_MAPPINGS_KEY]
                          [$item->getGroupId()]
            ) &&
            isset($mapping[self::MAPPING_DOCMAN_ROOT_KEY]
                          [self::MAPPING_MAPPINGS_KEY]
                          [$item->getGroupId()]
                          [self::MAPPING_PROPERTIES_KEY]
            );
    }

    private function getCustomTextualMetadata(Docman_Item $item) {
        $this->metadata_factory->setRealGroupId($item->getGroupId());

        return $this->metadata_factory->getRealMetadataList(
            false,
            array(
                PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
                PLUGIN_DOCMAN_METADATA_TYPE_STRING
            )
        );
    }

    private function getCustomDateMetadata(Docman_Item $item) {
        $this->metadata_factory->setRealGroupId($item->getGroupId());

        return $this->metadata_factory->getRealMetadataList(
            false,
            array(PLUGIN_DOCMAN_METADATA_TYPE_DATE)
        );
    }

    private function getCustomPropertyName(Docman_Metadata $item_metadata) {
        return self::CUSTOM_PROPERTY_PREFIX . '_' . $item_metadata->getId();
    }

    public function getFileContent(Docman_Version $version) {
        return array('file' => $this->fileContentEncode($version->getPath()));
    }

    public function getWikiContent(array $wiki_metadata) {
        return array('content' => $wiki_metadata['content']);
    }

    public function getLinkContent(Docman_Item $item) {
        return array('content' => $item->getUrl());
    }
}
