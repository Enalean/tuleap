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

require_once 'ClientFacade.class.php';
require_once dirname(__FILE__) .'/../FullTextSearch/ISearchDocuments.class.php';
require_once 'SearchResultCollection.class.php';

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
     * @see ISearchAndIndexDocuments::searchDocuments
     */
    public function searchDocuments($terms) {
        $query  = $this->getSearchDocumentsQuery($terms);
        $search = $this->client->search($query);
        return new ElasticSearch_SearchResultCollection($search, $this->project_manager);
    }
    
    private function getSearchDocumentsQuery($terms) {
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
    
    /**
     * @see ISearchAndIndexDocuments::getStatus
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
