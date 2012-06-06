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

class FullTextSearch_SearchPresenter {
    private $terms;
    private $query_result;
    private $index_status;
    private $project_manager;
    
    public function __construct($terms, $query_result, $index_status, ProjectManager $project_manager) {
        $this->terms           = $terms;
        $this->index_status    = $index_status;
        $this->query_result    = $query_result;
        $this->project_manager = $project_manager;
    }
    
    public function index_size() {
        return $this->index_status['indices']['tuleap']['index']['size'];
    }
    
    public function nb_docs() {
        return $this->index_status['indices']['tuleap']['docs']['num_docs'];
    }
    
    public function has_results() {
        return ($this->result_count() > 0);
    }
    
    public function terms() {
        return $this->terms;
    }
    
    public function result_count() {
        if (isset($this->query_result['hits']['total'])) {
            return $this->query_result['hits']['total'];
        }
        return 0;
    }
    
    public function search_results() {
        $results = array();
        if (isset($this->query_result['hits']['hits'])) {
            foreach ($this->query_result['hits']['hits'] as $hit) {
                $project = $this->project_manager->getProject($hit['fields']['group_id']);
                $results[] = array(
                    'item_title'   => $hit['fields']['title'],
                    'url'          => '/plugins/docman/?group_id='.$hit['fields']['group_id'].'&id='.$hit['fields']['id'].'&action=details',
                    'permissions'  => implode(', ', $hit['fields']['permissions']),
                    'project_name' => $project->getPublicName(),
                    'highlight'    => isset($hit['highlight']['file']) ? array_shift($hit['highlight']['file']) : ''
                );
            }
        }
        return $results;
    }
    
    public function elapsed_time() {
        if (isset($this->query_result['time'])) {
            return $this->query_result['time'];
        }
        return '';
    }

}

?>
