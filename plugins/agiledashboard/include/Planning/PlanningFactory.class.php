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
require_once('Planning.class.php');

class PlanningFactory {
    
    /**
     * @var PlanningDao
     */
    private $dao;
    
    public function __construct(PlanningDao $dao) {
        $this->dao = $dao;
    }
    
    /**
     * @param int $group_id
     *
     * @return array of Planning
     */
    public function getPlannings($group_id) {
        $plannings = array();
        foreach ($this->dao->searchPlannings($group_id) as $row) {
            $plannings[] = new Planning($row['id'], $row['name']);
        }
        return $plannings;
    }
    
    public function getPlanning($planning_id) {
        //
    }
    
    public function create($planning_name, $group_id, $planning_backlog_ids, $planning_release_id) {
        return $this->dao->create($planning_name, $group_id, $planning_backlog_ids, $planning_release_id);
    }
    
    public function deletePlanning($planning_id) {
        return $this->dao->delete($planning_id);
    }
}

?>
