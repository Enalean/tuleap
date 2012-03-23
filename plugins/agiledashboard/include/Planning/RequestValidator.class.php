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

require_once 'common/valid/ValidFactory.class.php';
require_once 'common/include/Codendi_Request.class.php';

class Planning_RequestValidator {
    
    /**
     * @var Valid_String
     */
    private $name;
    
    /**
     * @var Valid_UInt 
     */
    private $backlog_tracker_ids;
    
    /**
     * @var Valid_UInt
     */
    private $planning_tracker_id;
    
    public function __construct() {
        $this->name = new Valid_String('planning_name');
        $this->name->required();
        
        $this->backlog_tracker_ids = new Valid_UInt('backlog_tracker_ids');
        $this->backlog_tracker_ids->required();
        
        $this->planning_tracker_id = new Valid_UInt('planning_tracker_id');
        $this->planning_tracker_id->required();
    }
    
    public function isValid(Codendi_Request $request) {
        return $request->valid($this->name)
            && $request->validArray($this->backlog_tracker_ids)
            && $request->valid($this->planning_tracker_id);
    }
}
?>
