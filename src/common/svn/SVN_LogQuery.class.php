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
 * Wraps the available search criteria when retrieving revisions from SVN log.
 * 
 * Also translates some parameters (e.g. author_id to author_name).
 */
class SVN_LogQuery {
    
    /**
     * @var int
     */
    private $limit;
    
    /**
     * @var string
     */
    private $author_name;
    
    public function __construct($limit, $author_name) {
        $this->limit       = $limit;
        $this->author_name = $author_name;
    }
    
    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }
    
    /**
     * @return string
     */
    public function getAuthorName() {
        return $this->author_name;
    }
}
?>
