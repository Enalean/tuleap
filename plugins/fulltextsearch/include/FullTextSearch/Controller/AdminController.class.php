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

/**
 * Controller for site admin views
 */
class FullTextSearch_Controller_Admin extends FullTextSearch_Controller_Search {

    public function __construct(Codendi_Request                         $request,
                                FullTextSearch_ISearchDocumentsForAdmin $client) {
        parent::__construct($request, $client);
    }

    public function getIndexStatus() {
        return $this->client->getStatus();
    }

    public function index() {
        try {
            $index_status = $this->getIndexStatus();
            $presenter    = new FullTextSearch_Presenter_Index($index_status);
        } catch (ElasticSearchTransportHTTPException $e) {
            $presenter = new FullTextSearch_Presenter_ErrorNoSearch($e->getMessage());
        }
        $this->render($presenter->template, $presenter);
    }

    protected function getSearchPresenter($terms, $search_result) {
        $index_status  = $this->getIndexStatus();
        return new FullTextSearch_Presenter_AdminSearch($index_status, $terms, $search_result);
    }

    protected function render($template, $presenter) {
        if (!$this->request->isAjax()) {
            $GLOBALS['HTML']->header(array('title' => 'Full text search', 'selected_top_tab' => 'admin'));
        }

        parent::render($presenter->template, $presenter);

        if (!$this->request->isAjax()) {
            $GLOBALS['HTML']->footer(array());
        }
    }
}

?>