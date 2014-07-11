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
 * I build data for ElasticSearch 1.2 wiki requests
 */

class ElasticSearch_1_2_RequestWikiDataFactory {

    const MAPPING_PROPERTIES_KEY      = 'properties';
    const MAPPING_MAPPINGS_KEY        = 'mappings';
    const MAPPING_WIKI_ROOT_KEY       = 'wiki';

    const ELASTICSEARCH_STRING_TYPE   = 'string';
    const ELASTICSEARCH_DATE_TYPE     = 'date';
    const UNPARSABLE_TYPE             =  null;

    /**
     * Builds the data needed for
     * the very first PUT /wiki/:project_id/_mapping
     *
     * @param int $project_id
     *
     * @return array
     */
    public function getPUTMappingData($project_id) {
        $mapping_data                   = $this->initializePUTMappingData($project_id);

        $mapping_data[$project_id][self::MAPPING_PROPERTIES_KEY] = $this->getHardcodedMetadata();

        return $mapping_data;
    }

    private function initializePUTMappingData($project_id) {
        return array(
            (string) $project_id => array(
                self::MAPPING_PROPERTIES_KEY => array()
            )
        );
    }

    private function getHardcodedMetadata() {
        return array(
            'last_modified_date' => array(
                'type' => 'date'
            ),
            'last_author' => array(
                'type' => 'string'
            ),
            'last_summary' => array(
                'type' => 'string'
            ),
            'content' => array(
                'type' => 'string'
            ),
            'permissions' => array(
                'type'  => 'string',
                'index' => 'not_analyzed'
            )
        );
    }
}
