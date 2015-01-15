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

require_once 'common/mvc2/PluginController.class.php';

/**
 * Controller for basic fulltext searches
 */
class FullTextSearch_Controller_Search extends MVC2_PluginController {

    /**
     * @var FullTextSearch_ISearchDocuments
     */
    protected $client;

    public function __construct(Codendi_Request                 $request,
                                FullTextSearch_ISearchDocuments $client) {
        parent::__construct('fulltextsearch', $request);
        $this->client = $client;
    }

    public function searchSpecial(array $fields, $request = null) {
        $offset = $this->request->getValidated('offset', 'uint', 0);

        if (empty($request)) {
            return array();
        }

        /* This request should return a simple array with artifact ids as keys and changesets ids as values
        then delegate search result presentation to TV5 report */
        try {
            return $this->client->searchInFields($fields, $request, $offset);
        } catch (ElasticSearchTransportHTTPException $e) {
            echo $e->getMessage();
        }

        return array();
    }

    public function siteSearch(array $params) {
        $query  = $params['query'];
        $terms  = $query->getWords();
        $facets = $this->getFacets();
        $offset = $query->getOffset();

        try {
            $search_result = $this->client->searchDocuments(
                $terms,
                $facets,
                $offset,
                $this->request->getCurrentUser(),
                $query->getNumberOfResults()
            );

            $results_count      = $search_result->count();
            $maybe_more_results = ($results_count < $query->getNumberOfResults()) ? false :  true;
            if ($offset > 0) {
                $results_presenter = new FullTextSearch_Presenter_SearchMore($search_result);
            } else {
                $results_presenter = new FullTextSearch_Presenter_Search(
                    $search_result,
                    $maybe_more_results,
                    $terms,
                    $this->getDocumentSearchTypes($facets)
                );
            }

            $params['results']->setHasMore($maybe_more_results)->setCountResults($results_count);
        } catch (ElasticSearchTransportHTTPException $e) {
            $results_presenter = new FullTextSearch_Presenter_ErrorNoSearch($e->getMessage());
            $params['results']->setHasMore(false)->setCountResults(0);
        }

        $params['results']->setResultsHtml($this->renderToString($results_presenter->template, $results_presenter));
    }

    private function getFacets() {
        $facets = $this->request->get('facets');
        if (! is_array($facets)) {
            $facets = array();
        }
        return $facets;
    }

    private function getDocumentSearchTypes(array $facets) {
        $types = array();
        $types[] = array(
            'key'       => 'wiki',
            'name'      => $GLOBALS['Language']->getText('wiki_service', 'search_wiki_type'),
            'info'      => false,
            'available' => true,
        );

        $em = EventManager::instance();
        $em->processEvent(
            FULLTEXTSEARCH_EVENT_FETCH_ALL_DOCUMENT_SEARCH_TYPES,
            array(
                'all_document_search_types' => &$types
            )
        );

        $this->setSelectedSearchTypes($types, $facets);

        return $types;
    }

    private function setSelectedSearchTypes(array &$types, $facets) {
        foreach ($types as $i => $type) {
            if ($type['key'] == 'tracker') {
                $types[$i]['checked'] = false;
            } elseif (! $facets || isset($facets[$type['key']])) {
                $types[$i]['checked'] = true;
            } else {
                $types[$i]['checked'] = false;
            }
        }
    }

}
