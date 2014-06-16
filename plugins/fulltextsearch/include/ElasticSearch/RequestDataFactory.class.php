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

class ElasticSearch_1_2_RequestDataFactory {

    const EXCLUDED_HARDCODED_METADATA = 'status';
    const PROPERTIES_KEY              = 'properties';

    const ELASTICSEARCH_STRING_TYPE   = 'string';
    const ELASTICSEARCH_DATE_TYPE     = 'date';
    const UNPARSABLE_TYPE             =  null;

    /**
     * Builds the data needed for PUT /docman/:project_id/_mapping
     *
     * @param array $hardcoded_metadata
     * @param type  $project_id
     *
     * @return array
     */
    public function getPUTMappingData(array $hardcoded_metadata, $project_id) {
        $mapping_data                   = $this->initializePUTMappingData($project_id);
        $hardcoded_metadata_for_mapping = array();

        foreach ($hardcoded_metadata as $metadata) {
            $hardcoded_metadata_for_mapping[$metadata->getLabel()] = array(
                'type' => $this->getElasticSearchMappingType($metadata->getType())
            );
        }

        $this->removeUnparsableMetadata($hardcoded_metadata_for_mapping);
        $this->addStandardFileMetadata($hardcoded_metadata_for_mapping);
        $this->addStandardPermissionsMetadata($hardcoded_metadata_for_mapping);

        $mapping_data[$project_id][self::PROPERTIES_KEY] = $hardcoded_metadata_for_mapping;

        return $mapping_data;
    }

    private function initializePUTMappingData($project_id) {
        return array(
            (string) $project_id => array(
                'properties' => array()
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

    private function addStandardPermissionsMetadata(array &$hardcoded_metadata_for_mapping) {
        $hardcoded_metadata_for_mapping['permissions'] = array(
            'type'   => 'string',
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
}