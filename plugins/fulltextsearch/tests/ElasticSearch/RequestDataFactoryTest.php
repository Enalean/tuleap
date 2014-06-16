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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) .'/../../include/autoload.php';

class RequestDataFactoryTest extends TuleapTestCase {

    public function itBuildsDataForPutRequestCreateMapping() {
        $hardcoded_metadata_title = stub('Docman_Metadata')->getLabel()->returns('title');
        stub($hardcoded_metadata_title)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $hardcoded_metadata_description = stub('Docman_Metadata')->getLabel()->returns('description');
        stub($hardcoded_metadata_description)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $hardcoded_metadata_owner = stub('Docman_Metadata')->getLabel()->returns('owner');
        stub($hardcoded_metadata_owner)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $hardcoded_metadata_create_date = stub('Docman_Metadata')->getLabel()->returns('create_date');
        stub($hardcoded_metadata_create_date)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $hardcoded_metadata_update_date = stub('Docman_Metadata')->getLabel()->returns('update_date');
        stub($hardcoded_metadata_update_date)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $hardcoded_metadata_status = stub('Docman_Metadata')->getLabel()->returns('status');
        stub($hardcoded_metadata_status)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $hardcoded_metadata_obsolescence_date = stub('Docman_Metadata')->getLabel()->returns('obsolescence_date');
        stub($hardcoded_metadata_obsolescence_date)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $hardcoded_metadata = array(
            $hardcoded_metadata_title,
            $hardcoded_metadata_description,
            $hardcoded_metadata_owner,
            $hardcoded_metadata_create_date,
            $hardcoded_metadata_update_date,
            $hardcoded_metadata_update_date,
            $hardcoded_metadata_obsolescence_date
        );

        $project_id = 200;

        $request_data_factory = new ElasticSearch_1_2_RequestDataFactory();

        $expected_data = array(
            '200' => array(
                'properties' => array(
                    'title' => array(
                        'type' => 'string'
                    ),
                    'description' => array(
                        'type' => 'string'
                    ),
                    'owner' => array(
                        'type' => 'string'
                    ),
                    'create_date' => array(
                        'type' => 'date'
                    ),
                    'update_date' => array(
                        'type' => 'date'
                    ),
                    'obsolescence_date' => array(
                        'type' => 'date'
                    ),
                    'file' => array(
                        'type'   => 'attachment',
                        'fields' => array(
                            'title' => array(
                                'store' => 'yes'
                            ),
                            'file' => array(
                                'term_vector' => 'with_positions_offsets',
                                'store'       => 'yes'
                            )
                        )
                    ),
                    'permissions' => array(
                        'type'  => 'string',
                        'index' => 'not_analyzed'
                    )
                )
            )

        );

        $this->assertEqual(
            $expected_data,
            $request_data_factory->getPUTMappingData($hardcoded_metadata, $project_id)
        );
    }

}
