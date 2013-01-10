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

require_once 'common/mvc2/Controller.class.php';

/**
 * Controller for basic fulltext searches
 */
class FullTextSearch_Controller_Search extends MVC2_Controller {

    /**
     * @var FullTextSearch_ISearchDocuments
     */
    protected $client;

    public function __construct(Codendi_Request                 $request,
                                FullTextSearch_ISearchDocuments $client) {
        parent::__construct('fulltextsearch', $request);
        $this->client = $client;
    }

    public function search() {
        $terms  = $this->request->getValidated('words', 'string', '');
        $facets = $this->getFacets();
        $offset = $this->request->getValidated('offset', 'uint', 0);

        try {
            $search_result = $this->client->searchDocuments($terms, $facets, $offset, $this->request->getCurrentUser());
            if ($this->request->isAjax()) {
                $presenter = new FullTextSearch_Presenter_SearchOnlyResults($search_result);
            } else {
                $presenter = $this->getSearchPresenter($terms, $search_result);
            }
        } catch (ElasticSearchTransportHTTPException $e) {
            $presenter = new FullTextSearch_Presenter_ErrorNoSearch($e->getMessage());
        }
        $this->render($presenter->template, $presenter);
    }

    public function searchFollowups($request) {
        $facets = $this->getFacets();
        $offset = $this->request->getValidated('offset', 'uint', 0);
        $search_result = array();
        try {
            $search_result = $this->client->searchFollowups($request, $facets, $offset, $this->request->getCurrentUser());
        } catch (ElasticSearchTransportHTTPException $e) {
            echo $e->getMessage();
        }
        return $search_result;
    }

    protected function getSearchPresenter($terms, $search_result) {
        return new FullTextSearch_Presenter_Search(null, $terms, $search_result);
    }

    private function getFacets() {
        $facets = $this->request->get('facets');
        if (!is_array($facets)) {
            $facets = array();
        }
        return $facets;
    }
}

?>