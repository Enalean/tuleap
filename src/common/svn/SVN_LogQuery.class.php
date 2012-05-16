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
     * @var int
     */
    private $author_id;
    
    /**
     * @var UserManager
     */
    private $user_manager;
    
    public function __construct($limit, $author_id) {
        $this->limit     = $limit;
        $this->author_id = $author_id;
        
        $this->user_manager = UserManager::instance();
    }
    
    /**
     * Use for testing purposes only.
     */
    public function setUserManager(UserManager $user_manager) {
        $this->user_manager = $user_manager;
    }
    
    /**
     * Retrieve the revisions limit (50 by default).
     * 
     * @return int
     */
    public function getLimit() {
        if (! $this->limit) {
            $this->limit = 50;
        }
        return $this->limit;
    }
    
    /**
     * Retrieve name of user matching input author id.
     * Returns an empty string if no user matches the id.
     * 
     * @return string
     */
    public function getAuthorName() {
        $author = $this->user_manager->getUserById($this->author_id);
        
        return $author ? $author->getUserName() : '';
    }
}
?>
