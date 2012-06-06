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

require_once 'SearchResult.class.php';

class ElasticSearch_SearchResultCollection {
    public $nb_documents_found = 0;
    public $query_time         = 0;
    public $results            = array();
    
    public function __construct(array $result, ProjectManager $project_manager) {
        if (isset($result['hits']['total'])) {
            $this->nb_documents_found = $result['hits']['total'];
        }
        if (isset($result['time'])) {
            $this->query_time = $result['time'];
        }
        if (isset($result['hits']['hits'])) {
            foreach ($result['hits']['hits'] as $hit) {
                $this->results[] = new ElasticSearch_SearchResult($hit, $project_manager);
            }
        }
    }
}

?>
