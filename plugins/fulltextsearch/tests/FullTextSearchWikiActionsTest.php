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

require_once dirname(__FILE__) .'/../include/autoload.php';
require_once dirname(__FILE__).'/Constants.php';
require_once dirname(__FILE__).'/builders/Parameters_Builder.php';

class FullTextSearchWikiActionsTests extends TuleapTestCase {
    /** @var ElasticSearch_IndexClientFacade*/
    protected $client;

    /** @var FullTextSearchWikiActions */
    protected $actions;

    /** @var Wiki_PermissionsManager*/
    protected $permissions_manager;

    /** @var WikiPage */
    protected $wiki_page;

    public function setUp() {
        parent::setUp();

        $this->client = partial_mock(
            'ElasticSearch_IndexClientFacade',
            array('index', 'update', 'delete', 'getMapping', 'setMapping', 'getIndexedElement')
        );

        $this->wiki_page = mock('WikiPage');
        stub($this->wiki_page)->getGid()->returns(200);
        stub($this->wiki_page)->getId()->returns(101);
        stub($this->wiki_page)->getPagename()->returns('page');

        $this->permissions_manager = stub('Wiki_PermissionsManager')->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
            ->returns(array('@site_active'));

        $this->request_data_factory = new ElasticSearch_1_2_RequestWikiDataFactory(
            $this->permissions_manager
        );

        $this->actions = new FullTextSearchWikiActions(
            $this->client,
            $this->request_data_factory,
            mock('BackendLogger')
        );

    }

    public function itCallIndexOnClientWithRightParametersForEmptyWikiPage() {
        stub($this->client)->getMapping()->returns(array());
        stub($this->wiki_page)->getMetadata()->returns(array(
           'mtime' => 1405671338
        ));

        $expected = array(
            200,
            101,
            array(
                'id'                 => 101,
                'group_id'           => 200,
                'page_name'          => 'page',
                'last_modified_date' => '2014-07-18',
                'last_author'        => '',
                'last_summary'       => '',
                'content'            => '',
                'permissions'        => array('@site_active'),
               ),
        );
        $this->client->expectOnce('index', $expected);

        $this->actions->indexNewEmptyWikiPage($this->wiki_page);
    }

    public function itCallIndexOnClientWithRightParametersForWikiPage() {
        stub($this->client)->getMapping()->returns(array());
        stub($this->wiki_page)->getMetadata()->returns(array(
           'mtime'   => 1405671338,
           'content' => 'wiki page content',
           'author'  => 'author',
           'summary' => 'wiki page summary'
        ));

        $expected = array(
            200,
            101,
            array(
                'id'                 => 101,
                'group_id'           => 200,
                'page_name'          => 'page',
                'last_modified_date' => '2014-07-18',
                'last_author'        => 'author',
                'last_summary'       => 'wiki page summary',
                'content'            => 'wiki page content',
                'permissions'        => array('@site_active'),
               ),
        );
        $this->client->expectOnce('index', $expected);

        $this->actions->indexWikiPage($this->wiki_page);
    }

    public function itCanDeleteADocumentFromItsId() {
        $this->client->expectOnce('delete', array(200, 101));

        $this->actions->delete($this->wiki_page);
    }

    public function itDontDeleteADocumentIfitsNotPreviouslyIndexed() {
        stub($this->client)->getIndexedElement()->throws(new ElasticSearch_ElementNotIndexed);
        stub($this->client)->delete()->never();

        $this->actions->delete($this->wiki_page);
    }

    public function itReturnsTrueIfMappingIsNotEmptyForProject() {
        stub($this->client)->getMapping(200)->returns(array(
            'mappings' => array(
                '200' => array(
                    'properties' => array()
                )
            )
        ));

        $this->assertTrue($this->actions->checkProjectMappingExists(200));
    }

    public function itInitializesProjectMapping() {
        $expected_data = array(
            '200' => array(
                'properties' => array(
                    'page_name' =>  array(
                        'type' => 'string'
                    ),
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
                )
            )
        );

        expect($this->client)->setMapping(200, $expected_data)->once();

        $this->actions->initializeProjetMapping(200);
    }
}
