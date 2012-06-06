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

require_once 'SearchPresenter.class.php';
require_once 'common/mvc2/Controller.class.php';

class FullTextSearch_SearchController extends MVC2_Controller {
    
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
    
    public function search() {
        $terms         = $this->request->getValidated('terms', 'string', '');
        $index_status  = $this->getIndexStatus();
        $search_result = $this->getSearchResults($terms);

        $query_presenter = new FullTextSearch_SearchPresenter($terms, $search_result, $index_status, $this->project_manager);

        $title = 'Full text search';
        $GLOBALS['HTML']->header(array('title' => $title, 'toptab' => 'admin'));
        $this->render('query', $query_presenter);
        $GLOBALS['HTML']->footer(array());
    }
    
    private function getIndexStatus() {
        $this->client->setType('');
        return $this->client->request(array('_status'), 'GET', false);
    }
    
    private function getSearchResults($terms) {
        $search_result = array();
        if ($terms) {
            $this->client->setType('docman');
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
}

?>
