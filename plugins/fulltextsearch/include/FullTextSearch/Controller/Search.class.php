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

require_once dirname(__FILE__).'/../Presenter/Search.class.php';
require_once 'common/mvc2/Controller.class.php';

class FullTextSearch_Controller_Search extends MVC2_Controller {
    
    /**
     * @var ElasticSearch_ClientFacade 
     */
    private $client;
    
    /**
     * @var ProjectManager 
     */
    private $project_manager;
    
    public function __construct(Codendi_Request            $request,
                                ElasticSearch_ClientFacade $client,
                                ProjectManager             $project_manager) {
        parent::__construct('fulltextsearch', $request);
        
        $this->client          = $client;
        $this->project_manager = $project_manager;
    }
    
    public function index() {
        $index_status  = $this->client->getStatus();
        $presenter = new FullTextSearch_Presenter_Index($index_status);
        $this->renderWithHeaderAndFooter($presenter);
    }
    
    public function search() {
        $terms         = $this->request->getValidated('terms', 'string', '');
        $index_status  = $this->client->getStatus();
        $search_result = $this->getSearchResults($terms);
        $presenter     = new FullTextSearch_Presenter_Search($index_status, $terms, $search_result, $this->project_manager);
        $this->renderWithHeaderAndFooter($presenter);
    }
    
    private function getSearchResults($terms) {
        $search_result = array();
        if ($terms) {
            $search_result = $this->client->search($this->getSearchQuery($terms));
        }
        return $search_result;
    }
    
    private function getSearchQuery($terms) {
        return array(
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
                    'file' => new stdClass
                )
            )
        );
    }
    
    private function renderWithHeaderAndFooter($presenter) {
        $GLOBALS['HTML']->header(array('title' => 'Full text search', 'selected_top_tab' => 'admin'));
        $this->render($presenter->template, $presenter);
        $GLOBALS['HTML']->footer(array());
    }
}

?>
