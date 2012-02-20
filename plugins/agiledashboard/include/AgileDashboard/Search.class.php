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

require_once 'SearchDao.class.php';
require_once 'common/project/Project.class.php';

class AgileDashboard_Search {
    
    function getMatchingArtifacts(array $criteria) {
        $valueIds   = $this->extractValueIds($criteria);
        return $this->getDao()->searchMatchingArtifacts($valueIds);
    }

    protected function extractValueIds(array $criteria) {
        $sourceOrTargetValueIds   = array();
        foreach ($criteria as $fieldId => $data) {
            foreach ($data['values'] as $valueId) {
                if ($valueId != null)
                    $sourceOrTargetValueIds[] = (int)$valueId;
            }
        }
        $valueIdsList = array();
        
        foreach ($sourceOrTargetValueIds as $sourceOrTargetValueId) {
            $valueIds = array();
            
            foreach ($this->getDao()->searchSharedValueIds(array($sourceOrTargetValueId)) as $row) {
                $valueIds[] = $row['id'];
            }
            
            $valueIdsList[] = $valueIds;
        }
        
        return $valueIdsList;
    }
    
    /**
     * Wrapper for tests
     * @return AgileDashboardSearchDao
     */
    protected function getDao() {
        return new AgileDashboard_SearchDao();
    }

}
?>
