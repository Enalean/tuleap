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
require_once dirname(__FILE__).'/builders/Parameters_Builder.php';

class FullTextSearchWikiActionsTests extends TuleapTestCase {
    /** @var ElasticSearch_IndexClientFacade*/
    protected $client;

    /** @var FullTextSearchWikiActions */
    protected $actions;

    /** @var Wiki_PermissionsManager*/
    protected $permissions_manager;

    /** @var UserManager*/
    protected $user_manager;

    /** @var WikiPage */
    protected $wiki_page;

    /** @var PFUser */
    protected $user;

    public function setUp() {
        parent::setUp();

        $this->client = partial_mock(
            'ElasticSearch_IndexClientFacade',
            array('index', 'update', 'delete', 'deleteType', 'getMapping', 'setMapping', 'getIndexedElement', 'getIndexedType')
        );

        $this->wiki_page = mock('WikiPage');
        stub($this->wiki_page)->getGid()->returns(200);
        stub($this->wiki_page)->getId()->returns(101);
        stub($this->wiki_page)->getPagename()->returns('page');

        $this->permissions_manager = stub('Wiki_PermissionsManager')->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
            ->returns(array('@site_active'));

        $this->user         = aUser()->withId(123)->build();
        $this->user_manager = stub('UserManager')->getUserByUserName('*')->returns($this->user);

        $this->request_data_factory = new ElasticSearch_1_2_RequestWikiDataFactory(
            $this->permissions_manager,
            $this->user_manager
        );

        $this->actions = partial_mock(
            'FullTextSearchWikiActions',
            array('getAllIndexablePagesForProject'),
            array(
                $this->client,
                $this->request_data_factory,
                mock('TruncateLevelLogger')
            )
        );


    }

    public function itCallIndexOnClientWithRightParametersForEmptyWikiPage() {
        stub($this->client)->getMapping()->returns(array());
        stub($this->wiki_page)->getMetadata()->returns(array(
           'mtime'     => 1405671338,
           'author_id' => null
        ));

        $expected = array(
            200,
            101,
            array(
                'id'                 => 101,
                'group_id'           => 200,
                'page_name'          => 'page',
                'last_modified_date' => '2014-07-18T10:15:38+02:00',
                'last_author'        => null,
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
           'mtime'     => 1405671338,
           'content'   => 'wiki page content',
           'author'    => 'author',
           'author_id' => 123,
           'summary'   => 'wiki page summary'
        ));

        $expected = array(
            200,
            101,
            array(
                'id'                 => 101,
                'group_id'           => 200,
                'page_name'          => 'page',
                'last_modified_date' => '2014-07-18T10:15:38+02:00',
                'last_author'        => 123,
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

    public function itDontUpdateAWikiPageIfitsNotPreviouslyIndexed() {
        stub($this->client)->getIndexedElement()->throws(new ElasticSearch_ElementNotIndexed);
        stub($this->client)->update()->never();

        $this->actions->updatePermissions($this->wiki_page);
    }

    public function itCanUpdateAWikiPageFromItsId() {
        $expected = array(
            200,
            101,
            array(
                'permissions' => array('@site_active')
            )
        );
        $this->client->expectOnce('update', $expected);

        $this->actions->updatePermissions($this->wiki_page);
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
                )
            )
        );

        expect($this->client)->setMapping(200, $expected_data)->once();

        $this->actions->initializeProjetMapping(200);
    }

    public function itReindexAllTheWikiPagesForAGivenProject() {
        $wiki_page_1 = stub('WikiPage')->getId()->returns(101);
        $wiki_page_2 = stub('WikiPage')->getId()->returns(102);

        stub($wiki_page_1)->getGid()->returns(200);
        stub($wiki_page_2)->getGid()->returns(200);

        stub($this->actions)->getAllIndexablePagesForProject(200)->returns(
            array($wiki_page_1, $wiki_page_2)
        );

        expect($this->client)->getIndexedtype(200)->once();
        expect($this->client)->deleteType(200)->once();
        expect($this->client)->index(200, 101, '*')->at(0);
        expect($this->client)->index(200, 102, '*')->at(1);

        $this->actions->reIndexProjectWikiPages(200);
    }
}
