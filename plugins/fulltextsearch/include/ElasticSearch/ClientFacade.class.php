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

require_once dirname(__FILE__) .'/../FullTextSearch/ISearchAndIndexDocuments.class.php';

/**
 * Base class to interact with ElasticSearch
 */
class ElasticSearch_ClientFacade implements FullTextSearch_ISearchAndIndexDocuments {

    /**
     * @var ElasticSearchClient
     */
    private $client;

    /**
     * @var mixed
     */
    private $type;
    
    public function __construct(ElasticSearchClient $client, $type) {
        $this->client = $client;
        $this->type   = $type;
    }
    
    /**
     * @see ISearchAndIndexDocuments::index
     */
    public function index(array $document, $id = false) {
        $this->client->index($document, $id);
    }

    /**
     * @see ISearchAndIndexDocuments::delete
     */
    public function delete($id = false) {
        $this->client->delete($id);
    }

    /**
     * @see ISearchAndIndexDocuments::delete
     */
    public function update($item_id, $data) {
        $this->client->request($item_id.'/_update', 'POST', $data);
    }

    /**
     * make a parameter with name $nname and value $value
     * then append it to current_data as script and var
     */
    public function buildSetterData(array $current_data, $name, $value) {
        $current_data['script']       .= "ctx._source.$name = $name;";
        $current_data['params'][$name] = $value;
        return $current_data;
    }
    
    /**
     * Execute a search query
     * 
     * @param mixed $query
     * 
     * @return array
     */
    public function search($query) {
        return $this->client->search($query);
    }
    
    /**
     * Execute a query directly 
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
