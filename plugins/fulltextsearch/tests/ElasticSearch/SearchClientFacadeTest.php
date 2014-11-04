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

require_once dirname(__FILE__) .'/../../include/autoload.php';
require_once 'common/project/ProjectManager.class.php';

class ElasticSearch_SearchClientFacadeTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->user                = mock('PFUser');
        $project_manager           = mock('ProjectManager');
        $this->elasticsearchclient = mock('ElasticSearchClient');
        $user_manager              = stub('UserManager')->getCurrentUser()->returns($this->user);
        $result_factory            = new ElasticSearch_1_2_ResultFactory(
            $project_manager,
            new URLVerification(),
            $user_manager
        );

        $this->client = new ElasticSearch_SearchClientFacade(
            $this->elasticsearchclient,
            'whatever',
            $project_manager,
            $result_factory
        );

        $this->admin_client = new ElasticSearch_SearchAdminClientFacade(
            $this->elasticsearchclient,
            'whatever',
            $project_manager,
            $result_factory
        );
    }

    public function itAsksToElasticsearchToReturnOnlyResultsWithMatchingPermissions() {
        $this->assertExpectedQuery(new QueryExpectation(array(
            'query' => array (
                'filtered' => array (
                    'query' => array (
                        'query_string' => array (
                            'query' => 'some terms',
                        ),
                    ),
                    'filter' => array (
                        'terms' => array (
                            'permissions' => array (
                            ),
                        ),
                    ),
                ),
            )
        )));
    }

    public function itAsksToElasticsearchToReturnOnlyRelevantFields() {
        $this->assertExpectedQuery(new QueryExpectation(array(
            'fields' => array(
                'title',
                'page_name',
                'id',
                'group_id',
            )
        )));
    }

    public function itAsksToElasticsearchToReturnAlsoPermissionsForSiteAdminQuery() {
        $this->assertExpectedAdminQuery(new QueryExpectation(array(
            'fields' => array(
                'title',
                'page_name',
                'id',
                'group_id',
                'permissions'
            )
        )));
    }

    public function itAsksToElasticsearchToReturnFacets() {
        $this->assertExpectedQuery(new QueryExpectation(array(
            'facets' => array(
                'projects' => array(
                    'terms' => array(
                        'field' => 'group_id'
                    )
                ),
                'owner' => array(
                    'terms' => array(
                        'fields' => array('last_author', 'owner')
                    )
                )
            )
        )));
    }

    public function itAsksToElasticsearchToUseProjectsFacets() {
        $this->assertExpectedFacetedQuery(
            array(
                'group_id' => array('101', '102', '103')
            ),
            new QueryExpectation(array(
                'filter' => array(
                    'bool' => array(
                        'must' => array(
                            array(
                                'bool' => array(
                                    'should' => array(
                                        array(
                                            'term' => array(
                                                'group_id' => 101
                                            )
                                        ),
                                        array(
                                            'term' => array(
                                                'group_id' => 102
                                            )
                                        ),
                                        array(
                                            'term' => array(
                                                'group_id' => 103
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            ))
        );
    }

    public function itAsksToElasticsearchToUseMyProjectsFacets() {
        $this->assertExpectedFacetedQuery(
            array(
                ElasticSearch_SearchResultMyProjectsFacet::IDENTIFIER => '101,102,103'
            ),
            new QueryExpectation(array(
                'filter' => array(
                    'bool' => array(
                        'must' => array(
                            array(
                                'bool' => array(
                                    'should' => array(
                                        array(
                                            'term' => array(
                                                'group_id' => 101
                                            )
                                        ),
                                        array(
                                            'term' => array(
                                                'group_id' => 102
                                            )
                                        ),
                                        array(
                                            'term' => array(
                                                'group_id' => 103
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            ))
        );
    }

    public function itAsksToElasticsearchToUseOwnerFacets() {
        $this->assertExpectedFacetedQuery(
            array(
                ElasticSearch_SearchResultOwnerFacet::IDENTIFIER => '102'
            ),
            new QueryExpectation(array(
                'filter' => array(
                    'bool' => array(
                        'must' => array(
                            array(
                                'bool' => array(
                                    'should' => array(
                                        array(
                                            'term' => array(
                                                'owner' => 102
                                            )
                                        ),
                                        array(
                                            'term' => array(
                                                'last_author' => 102
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            ))
        );
    }

    public function itAsksToElasticsearchToGiveResultsFromTheOffset() {
        $this->assertExpectedQuery(new QueryExpectation(array(
            'from' => 666
        )));
    }

    private function assertExpectedQuery(QueryExpectation $query_excpectation) {
        $some_results = array();
        stub($this->elasticsearchclient)->search($query_excpectation)->once()->returns($some_results);
        $size = 10;

        $offset                     = 666;
        $no_facet_submitted_by_user = array();
        $search_type = null;
        $this->client->searchDocuments('some terms', $no_facet_submitted_by_user, $offset, $this->user, $size, $search_type);
    }

    private function assertExpectedFacetedQuery(array $facets, QueryExpectation $query_excpectation) {
        $some_results = array();
        stub($this->elasticsearchclient)->search($query_excpectation)->once()->returns($some_results);
        $size = 10;

        $offset      = 666;
        $search_type = null;
        $this->client->searchDocuments('some terms', $facets, $offset, $this->user, $size, $search_type);
    }

    private function assertExpectedAdminQuery(QueryExpectation $query_excpectation) {
        $some_results = array();
        stub($this->elasticsearchclient)->search($query_excpectation)->once()->returns($some_results);
        $size = 10;

        $offset                     = 666;
        $no_facet_submitted_by_user = array();
        $search_type = null;
        $this->admin_client->searchDocuments('some terms', $no_facet_submitted_by_user, $offset, $this->user, $size, $search_type);
    }
}

// For testing purpose
class ElasticSearchClient {
    public function search() {
    }
}

class QueryExpectation extends SimpleExpectation {

    private $expected;
    private $testMessage;

    function __construct(array $expected) {
        parent::__construct();
        $this->expected = $expected;
    }

    function test($compare) {
        foreach ($this->expected as $key => $value) {
            if (isset($compare[$key])) {
                $e = new EqualExpectation($value);
                if (!$e->test($compare[$key])) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }

    function testMessage($compare) {
        $message = '';
        foreach ($this->expected as $key => $value) {
            if (isset($compare[$key])) {
                $e = new EqualExpectation($value);
                if ($e->test($compare[$key])) {
                    $message .= $key . ' ok.';
                } else {
                    $message .= $key .' => '. $e->overlayMessage($compare[$key], $this->_dumper);
                }
            } else {
                return $key .' not found';
            }
        }
        return $message;
    }
}
?>
