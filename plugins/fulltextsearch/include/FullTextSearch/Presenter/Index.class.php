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

class FullTextSearch_Presenter_Index {
    public $template = 'index';
    
    private $index_status;
    private $terms;
    
    public function __construct($index_status, $terms = '') {
        $this->index_status = $index_status;
        $this->terms        = $terms;
        $this->search_label = $GLOBALS['Language']->getText('search_index', 'search');
    }
    
    public function index_size() {
        return $this->index_status['size'];
    }
    
    public function nb_docs() {
        return $this->index_status['nb_docs'];
    }
    
    public function terms() {
        return $this->terms;
    }
}

?>