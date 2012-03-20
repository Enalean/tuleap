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

require_once('PlanningDao.class.php');

class PlanningFactory {
    
    /**
     * Hold an instance of the class
     */
    protected static $instance;
    
    
    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct() {}
    
    /**
     * The singleton method
     *
     * @return Tracker_ArtifactFactory an instance of this class
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }
    
    public function getPlannings($group_id) {
        return $this->getDao()->searchPlannings($group_id);
    }
    
    public function create($planning_name, $planning_backlog_ids, $planning_release_id) {
        return $this->getDao()->create($planning_name, $planning_backlog_ids, $planning_release_id);
    }
    
    public function getDao() {
        return new PlanningDao();
    }
}

?>
