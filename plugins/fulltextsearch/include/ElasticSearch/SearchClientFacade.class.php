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

require_once 'common/project/UGroupLiteralizer.class.php';
require_once 'common/project/ProjectManager.class.php';

/**
 * Allow to perform search on ElasticSearch Index
 */
class ElasticSearch_SearchClientFacade extends ElasticSearch_ClientFacade implements FullTextSearch_ISearchDocuments {

    /**
     * @var mixed
     */
    protected $type;

    /**
     * @var ProjectManager
     */
    protected $project_manager;

    public function __construct(ElasticSearchClient $client, $type, ProjectManager $project_manager) {
        parent::__construct($client);
        $this->type            = $type;
        $this->project_manager = $project_manager;
    }

    /**
     * @see ISearchDocuments::searchDocuments
     *
     * @return ElasticSearch_SearchResultCollection
     */
    public function searchFollowups($request, array $facets, $offset, PFUser $user) {
        $terms   = trim($request->getValidated('search_followups', 'string', ''));
        $results = array();
        if ($terms) {
            $query        = $this->getSearchFollowupsQuery($terms, $facets, $offset, $user);
            $searchResult = $this->client->search($query);
            if (!empty($searchResult['hits']['total'])) {
                foreach ($searchResult['hits']['hits'] as $hit) {
                    $results[$hit['fields']['artifact_id']] = $hit['fields']['changeset_id'];
                }
            }
        }
        return $results;
    }

    /**
     * @see ISearchDocuments::searchDocuments
     *
     * @return ElasticSearch_SearchResultCollection
     */
    public function searchDocuments($terms, array $facets, $offset, PFUser $user) {
        $query  = $this->getSearchDocumentsQuery($terms, $facets, $offset, $user);
        // For debugging purpose, uncomment the statement below to see the
        // content of the request (can be directly injected in a curl request)
        //var_dump(json_encode($query));
        $search = $this->client->search($query);
        return new ElasticSearch_SearchResultCollection($search, $facets, $this->project_manager, $this->type);
    }

    /**
     * @return array to be used for querying ES
     */
    protected function getSearchFollowupsQuery($terms, array $facets, $offset, PFUser $user) {
        $query = array(
            'from' => (int)$offset,
            'query' => array(
                'query_string' => array(
                    'query' => $terms
                )
            ),
            'fields' => array(
                'id',
                'group_id',
                'artifact_id',
                'changeset_id'
            )
        );
        //$this->filterWithGivenFacets($query, $facets);
        //$this->filterQueryWithPermissions($query, $user);
        return $query;
    }

    /**
     * @return array to be used for querying ES
     */
    protected function getSearchDocumentsQuery($terms, array $facets, $offset, PFUser $user) {
        $query = array(
            'from' => (int)$offset,
            'query' => array(
                'query_string' => array(
                    'query' => $terms
                )
            ),
            'fields' => array(
                'id',
                'group_id',
                'title',
            ),
            'highlight' => array(
                'pre_tags' => array('<em class="fts_word">'),
                'post_tags' => array('</em>'),
                'fields' => array(
                    'file' => new stdClass()
                )
            ),
            'facets' => array(
                'projects' => array(
                    'terms' => array(
                        'field' => 'group_id'
                    )
                )
            ),
        );
        $this->filterWithGivenFacets($query, $facets);
        $this->filterQueryWithPermissions($query, $user);
        return $query;
    }

    protected function filterQueryWithPermissions(array &$query, PFUser $user) {
        $ugroup_literalizer = new UGroupLiteralizer();
        $filtered_query = array(
            'filtered' => array(
                'query'  => $query['query'],
                'filter' => array(
                    'terms' => array(
                        'permissions' => $ugroup_literalizer->getUserGroupsForUserWithArobase($user)
                    )
                )
            )
        );
        $query['query'] = $filtered_query;
    }

    private function filterWithGivenFacets(array &$query, array $facets) {
        if (isset($facets['group_id'])) {
            $filter_on_project = array('or' => array());
            foreach ($facets['group_id'] as $group_id) {
                $filter_on_project['or'][] = array(
                    'range' => array(
                        'group_id' => array(
                            'from' => $group_id,
                            'to'   => $group_id
                        )
                    )
                );
            }
            $query['filter'] = $filter_on_project;
        }
    }
}

?>
