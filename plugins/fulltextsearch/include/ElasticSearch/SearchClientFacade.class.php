<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
     * @see ISearchDocuments::searchDocuments
     * @see https://github.com/nervetattoo/elasticsearch
     *
     * @return ElasticSearch_SearchResultCollection
     */
    public function searchDocuments($terms, array $facets, $offset, PFUser $user, $size) {
        $query  = $this->getSearchDocumentsQuery($terms, $facets, $offset, $user, $size);
        // For debugging purpose, uncomment the statement below to see the
        // content of the request (can be directly injected in a curl request)
        // echo "<pre>".json_encode($query)."</pre>";

        $this->setTypesForRequest($facets);

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
    protected function getSearchDocumentsQuery($terms, array $facets, $offset, PFUser $user, $size) {
        $returned_fields = array('id', 'group_id');
        $this->addFieldsForDocman($returned_fields);
        $this->addFieldsForWiki($returned_fields);
        $this->addFieldsForTracker($returned_fields);

        $query = array(
            'from'      => (int)$offset,
            'size'      => $size,
            'fields'    => $returned_fields,
            'highlight' => array(
                'pre_tags'  => array('<em class="fts_word">'),
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
        $this->filterQueryWithPermissions($query, $user, $terms, $facets);
        return $query;
    }

    private function addFieldsForDocman(&$returned_fields) {
        $returned_fields[] = 'title';
    }

    private function addFieldsForWiki(&$returned_fields) {
        $returned_fields[] = 'page_name';
    }

    private function addFieldsForTracker(&$returned_fields) {
        $returned_fields[] = 'last_changeset_id';
    }

    private function getHighlightFieldsForDocman() {
        return array('file' => new stdClass());
    }

    private function getHighlightFieldsForWiki() {
        return array('content' => new stdClass());
    }

    protected function filterQueryWithPermissions(array &$query, PFUser $user, $terms, $facets) {
        $ugroup_literalizer = new UGroupLiteralizer();
        $ugroups = $ugroup_literalizer->getUserGroupsForUserWithArobase($user);

        $types          = $this->getTypesForFacets($facets);
        $document_types = array(ElasticSearch_SearchResultWiki::TYPE_IDENTIFIER, ElasticSearch_SearchResultDocman::TYPE_IDENTIFIER);
        $tracker_types  = array(ElasticSearch_SearchResultTracker::TYPE_IDENTIFIER);

        if (array_intersect($types, $tracker_types) && array_intersect($types, $document_types)) {
            $query_part = array(
                'bool' => array(
                    'should' => array(
                        $this->getDocumentQueryPart($terms, $ugroups),
                        $this->getTrackerQueryPart($user, $terms, $ugroups, $facets)
                    ),
                )
            );
        } elseif (array_intersect($types, $document_types)) {
            $query_part = $this->getDocumentQueryPart($terms, $ugroups);
        } elseif (array_intersect($types, $tracker_types)) {
            $query_part = $this->getTrackerQueryPart($user, $terms, $ugroups, $facets);
        }

        $query['query'] = $query_part;
    }

    private function getDocumentQueryPart($terms, array $ugroups) {
        return array(
            'bool' => array(
                'must' => array(
                   'terms' => array(
                        'permissions' => $ugroups
                    ),
                   'query'  => array(
                        'query_string' => array(
                            'query' => $terms,
                        )
                    )
                )
            )
        );
    }

    private function getTrackerQueryPart(PFUser $user, $terms, array $ugroups, array $facets) {
        $tracker_fields = array();

        $em = EventManager::instance();
        $em->processEvent(
            FULLTEXTSEARCH_EVENT_FETCH_PROJECT_TRACKER_FIELDS,
            array(
                'fields'     => &$tracker_fields,
                'user'       => $user,
                'project_id' => array_shift($facets[ElasticSearch_SearchResultProjectsFacetCollection::IDENTIFIER])
            )
        );

        $should = array();
        foreach ($tracker_fields as $tracker_id => $fields) {
            $field_names = array();

            foreach ($fields as $field) {
                $field_names[] = $field->getName();
            }
            $field_names[] = ElasticSearch_1_2_RequestTrackerDataFactory::COMMENT_FIELD_NAME;

            $should[] = array(
                'bool' => array(
                    'must' => array(
                        array(
                            'multi_match' => array(
                                'fields' => $field_names,
                                'query'  => $terms,
                            )
                        ),
                        array(
                            'term' => array(
                                'tracker_id' => $tracker_id
                            )
                        )
                    )
                )
            );
        }

        return array(
            'bool' => array(
                'must' => array(
                    array(
                        'bool' => array(
                            'should' => $should
                        )
                    ),
                    array(
                        'terms' => array(
                            'tracker_ugroups' => $ugroups
                        ),
                    )
                ),
            )
        );
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
            $updated_date_filter['bool']['should'][] = array(
                'range' => array(
                    ElasticSearch_1_2_ArtifactPropertiesExtractor::LAST_UPDATE_PROPERTY => array(
                        'gte' => $facets[ElasticSearch_SearchResultUpdateDateFacetCollection::IDENTIFIER]
                    )
                )
            );

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

    public function setTypesForRequest(array $facets) {
        $types = $this->getTypesForFacets($facets);

        if ($types) {
            $this->client->setType($types);
        }
    }

    private function getTypesForFacets(array $facets) {
        $types = array();

        if (isset($facets[ElasticSearch_SearchResultDocman::TYPE_IDENTIFIER])) {
            $types[] = ElasticSearch_SearchResultDocman::TYPE_IDENTIFIER;
        }

        if (isset($facets[ElasticSearch_SearchResultWiki::TYPE_IDENTIFIER])) {
            $types[] = ElasticSearch_SearchResultWiki::TYPE_IDENTIFIER;
        }

        if (isset($facets[ElasticSearch_SearchResultTracker::TYPE_IDENTIFIER]) && isset($facets['group_id']) && count($facets['group_id']) == 1) {
            $types[] = ElasticSearch_SearchResultTracker::TYPE_IDENTIFIER;
        }

        if (count($types) == 0) {
            $types[] = ElasticSearch_SearchResultDocman::TYPE_IDENTIFIER;
            $types[] = ElasticSearch_SearchResultWiki::TYPE_IDENTIFIER;
        }

        return $types;
    }
}
