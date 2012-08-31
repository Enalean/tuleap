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

/**
 * Allow to perform search on ElasticSearch Index
 */
class ElasticSearch_SearchClientFacade extends ElasticSearch_ClientFacade implements FullTextSearch_ISearchDocuments {
    /**
     * @var mixed
     */
    private $type;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(ElasticSearchClient $client, $type, ProjectManager $project_manager) {
        parent::__construct($client);
        $this->type            = $type;
        $this->project_manager = $project_manager;
    }

    /**
     * @see ISearchDocuments::searchDocumentsIgnoingPermissions
     *
     * @return ElasticSearch_SearchResultCollection
     */
    public function searchDocumentsIgnoringPermissions($terms, array $facets) {
        $query  = $this->getSearchDocumentsQuery($terms, $facets);
        $search = $this->client->search($query);
        return new ElasticSearch_SearchResultCollection($search, $facets, $this->project_manager);
    }

    /**
     * @see ISearchDocuments::searchDocuments
     *
     * @return ElasticSearch_SearchResultCollection
     */
    public function searchDocuments($terms, array $facets, User $user) {
        $query  = $this->getSearchDocumentsQueryWithPermissions($terms, $facets, $user);
        $search = $this->client->search($query);
        return new ElasticSearch_SearchResultCollection($search, $facets, $this->project_manager);
    }

    /**
     * @return array to be used for querying ES
     */
    private function getSearchDocumentsQueryWithPermissions($terms, array $facets, User $user) {
        $ugroup_literalizer = new UGroupLiteralizer();
        $query = $this->getSearchDocumentsQuery($terms, $facets);
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
        $query['query']  = $filtered_query;
        $query['fields'] = array_diff($query['fields'], array('permissions'));
        //print_r(json_encode($query));
        return $query;
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

    /**
     * @return array to be used for querying ES
     */
    private function getSearchDocumentsQuery($terms, array $facets) {
        $query = array(
            'query' => array(
                'query_string' => array(
                    'query' => $terms
                )
            ),
            'fields' => array(
                'id',
                'group_id',
                'title',
                'permissions'
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
            )
        );
        $this->filterWithGivenFacets($query, $facets);
        return $query;
    }

    /**
     * @see ISearchDocuments::getStatus
     *
     * @return array
     */
    public function getStatus() {
        $this->client->setType('');
        $result = $this->client->request(array('_status'), 'GET', $payload = false, $verbose = true);
        $this->client->setType($this->type);

        $status = array(
            'size'    => isset($result['indices']['tuleap']['index']['size']) ? $result['indices']['tuleap']['index']['size'] : '0',
            'nb_docs' => isset($result['indices']['tuleap']['docs']['num_docs']) ? $result['indices']['tuleap']['docs']['num_docs'] : 0,
        );

        return $status;
    }
}

?>
