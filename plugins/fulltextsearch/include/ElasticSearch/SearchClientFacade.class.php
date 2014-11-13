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
 * Allow to perform search on ElasticSearch
 */
class ElasticSearch_SearchClientFacade extends ElasticSearch_ClientFacade implements FullTextSearch_ISearchDocuments {

    /**
     * @var mixed
     */
    protected $index;

    /**
     * @var ProjectManager
     */
    protected $project_manager;

    /**
     * @var UserManager
     */
    protected $user_manager;

    /** @var ElasticSearch_1_2_ResultFactory */
    protected $result_factory;

    public function __construct(
        ElasticSearchClient $client,
        $index,
        ProjectManager $project_manager,
        UserManager $user_manager,
        ElasticSearch_1_2_ResultFactory $result_factory
    ) {
        parent::__construct($client);
        $this->index           = $index;
        $this->project_manager = $project_manager;
        $this->user_manager    = $user_manager;
        $this->result_factory  = $result_factory;
    }

    /**
     * @see ISearchDocuments::searchDocuments
     *
     * @return ElasticSearch_SearchResultCollection
     */
    public function searchInFields(array $fields, $request, $offset) {
        $terms   = trim($request->getValidated('search_fulltext', 'string', ''));
        $results = array();
        if ($terms) {
            $query        = $this->getSearchInFieldsQuery($terms, $fields, $offset);
            $searchResult = $this->client->search($query);

            $results = $this->result_factory->getChangesetIds($searchResult);
        }

        return $results;
    }

    /**
     * @see ISearchDocuments::searchDocuments
     *
     * @return ElasticSearch_SearchResultCollection
     */
    public function searchDocuments($terms, array $facets, $offset, PFUser $user, $size) {
        $query  = $this->getSearchDocumentsQuery($terms, $facets, $offset, $user, $size);
        // For debugging purpose, uncomment the statement below to see the
        // content of the request (can be directly injected in a curl request)
        // echo "<pre>".json_encode($query)."</pre>";

        $search = $this->client->search($query);
        return new ElasticSearch_SearchResultCollection(
            $search,
            $facets,
            $this->result_factory
        );
    }

    /**
     * @return array to be used for querying ES
     */
    protected function getSearchInFieldsQuery($terms, $fields, $offset) {
        $query = array(
            'from' => (int)$offset,
            'query' => array(
                'multi_match' => array(
                    'query'  => $terms,
                    'fields' => $fields
                )
            ),
            'fields' => array(
                'id',
                'group_id',
                'last_changeset_id'
            )
        );
        return $query;
    }

    /**
     * @return array to be used for querying ES
     */
    protected function getSearchDocumentsQuery($terms, array $facets, $offset, PFUser $user, $size) {
        $returned_fields = array_merge($this->getReturnedFieldsForDocman(), $this->getReturnedFieldsForWiki());
        $returned_fields = array_merge($returned_fields, array('id', 'group_id'));

        $query = array(
            'from'  => (int)$offset,
            'size'  => $size,
            'query' => array(
                'query_string' => array(
                    'query' => $terms
                )
            ),
            'fields'    => $returned_fields,
            'highlight' => array(
                'pre_tags' => array('<em class="fts_word">'),
                'post_tags' => array('</em>'),
                'fields' => $this->getHighlightFieldsForDocman() + $this->getHighlightFieldsForWiki()
            ),
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
                ),
                'update_date' => array(
                    'terms' => array(
                        'fields' => array('last_modified_date', 'update_date')
                    )
                ),
            ),
        );

        $this->filterWithGivenFacets($query, $facets);
        $this->filterQueryWithPermissions($query, $user);
        return $query;
    }

    private function getReturnedFieldsForDocman() {
        return array('title');
    }

    private function getReturnedFieldsForWiki() {
        return array('page_name');
    }

    private function getHighlightFieldsForDocman() {
        return array('file' => new stdClass());
    }

    private function getHighlightFieldsForWiki() {
        return array('content' => new stdClass());
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
        $filter = array();

        $this->applyProjectFacets($filter, $facets);
        $this->applyOwnerFacet($filter, $facets);
        $this->applyUpdateDateFacet($filter, $facets);

        if (! empty($filter['bool']['must'])) {
            $query['filter'] = $filter;
        }
    }

    private function applyProjectFacets(array &$filter, array $facets) {
        if (isset($facets[ElasticSearch_SearchResultProjectsFacetCollection::IDENTIFIER])) {
            $this->createFacetsInQuery($filter);

            $project_filter = array('bool' => array('should' => array()));

            if (isset($facets[ElasticSearch_SearchResultProjectsFacetCollection::IDENTIFIER])) {
                $this->filterOnProjectIds($project_filter['bool']['should'], $facets[ElasticSearch_SearchResultProjectsFacetCollection::IDENTIFIER]);
            }

            if (! empty($project_filter['bool']['should'])) {
                $filter['bool']['must'][] = $project_filter;
            }
        }
    }

    private function applyOwnerFacet(array &$filter, array $facets) {
        if (isset($facets[ElasticSearch_SearchResultOwnerFacet::IDENTIFIER])) {
            $this->createFacetsInQuery($filter);

            $owner_filter                     = array('bool' => array('should' => array()));
            $owner_filter['bool']['should'][] = array('term' => array('owner'       => (int)$facets[ElasticSearch_SearchResultOwnerFacet::IDENTIFIER]));
            $owner_filter['bool']['should'][] = array('term' => array('last_author' => (int)$facets[ElasticSearch_SearchResultOwnerFacet::IDENTIFIER]));

            $filter['bool']['must'][] = $owner_filter;
        }
    }

    private function applyUpdateDateFacet(array &$filter, array $facets) {
        if (isset($facets[ElasticSearch_SearchResultUpdateDateFacetCollection::IDENTIFIER]) && ! empty($facets[ElasticSearch_SearchResultUpdateDateFacetCollection::IDENTIFIER])) {
            $this->createFacetsInQuery($filter);

            $updated_date_filter                     = array('bool' => array('should' => array()));
            $updated_date_filter['bool']['should'][] = array('range' => array('last_modified_date' => array('gte' => $facets[ElasticSearch_SearchResultUpdateDateFacetCollection::IDENTIFIER])));
            $updated_date_filter['bool']['should'][] = array('range' => array('update_date'        => array('gte' => $facets[ElasticSearch_SearchResultUpdateDateFacetCollection::IDENTIFIER])));

            $filter['bool']['must'][] = $updated_date_filter;
        }
    }

    private function createFacetsInQuery(array &$filter) {
        if (! isset($filter['bool'])) {
            $filter['bool'] = array('must' => array());
        }
    }

    private function filterOnProjectIds(array &$project_filter, array $group_ids) {
        foreach ($group_ids as $group_id) {
            if ($group_id === ElasticSearch_SearchResultProjectsFacetCollection::USER_PROJECTS_IDS_KEY) {
                $this->filterOnProjectIds($project_filter, $this->user_manager->getCurrentUser()->getProjects());

                return;
            }

            $project_filter[] = array(
                'term' => array(
                    'group_id' => (int)$group_id
                )
            );
        }
    }
}
