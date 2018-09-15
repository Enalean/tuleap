<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

use Tuleap\PHPWiki\WikiPage;

/**
 * I build data for ElasticSearch 1.2 wiki requests
 */

class ElasticSearch_1_2_RequestWikiDataFactory {

    const MAPPING_PROPERTIES_KEY      = 'properties';
    const MAPPING_MAPPINGS_KEY        = 'mappings';
    const MAPPING_WIKI_ROOT_KEY       = 'wiki';

    const PHPWIKI_METADATA_LAST_MODIFIED_DATE = 'mtime';
    const PHPWIKI_METADATA_AUTHOR_ID          = 'author_id';
    const PHPWIKI_METADATA_SUMMARY            = 'summary';
    const PHPWIKI_METADATA_CONTENT            = 'content';

    const ELASTICSEARCH_STRING_TYPE   = 'string';
    const ELASTICSEARCH_DATE_TYPE     = 'date';
    const UNPARSABLE_TYPE             =  null;

    /** @var Wiki_PermissionsManager */
    private $permissions_manager;

    /** @var UserManager */
    private $user_manager;

    public function __construct(Wiki_PermissionsManager $permissions_manager, UserManager $user_manager) {
        $this->permissions_manager = $permissions_manager;
        $this->user_manager        = $user_manager;
    }

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

    public function getIndexedWikiPageData(WikiPage $wiki_page) {
        $wiki_page_metadata = $wiki_page->getMetadata();

        return array(
            'id'                 => $wiki_page->getId(),
            'group_id'           => $wiki_page->getGid(),
            'page_name'          => $wiki_page->getPagename(),
            'last_modified_date' => date('c', $wiki_page_metadata[self::PHPWIKI_METADATA_LAST_MODIFIED_DATE]),
            'last_author'        => $wiki_page_metadata[self::PHPWIKI_METADATA_AUTHOR_ID],
            'last_summary'       => isset($wiki_page_metadata[self::PHPWIKI_METADATA_SUMMARY]) ?
                $wiki_page_metadata[self::PHPWIKI_METADATA_SUMMARY] : '',
            'content'            => isset($wiki_page_metadata[self::PHPWIKI_METADATA_CONTENT]) ?
                $wiki_page_metadata[self::PHPWIKI_METADATA_CONTENT] : '',
            'permissions'        => $this->getCurrentPermissions($wiki_page)
        );
    }

    public function getCurrentPermissions(WikiPage $wiki_page) {
        return $this->permissions_manager->getFromattedUgroupsThatCanReadWikiPage($wiki_page);
    }

    public function setUpdatedData(array &$current_data, $name, $value) {
        $current_data[$name] = $value;
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
            'page_name' =>  array(
                'type' => 'string'
            ),
            'last_modified_date' => array(
                'type' => 'date',
                'format' => 'date_time_no_millis'
            ),
            'last_author' => array(
                'type' => 'long'
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
