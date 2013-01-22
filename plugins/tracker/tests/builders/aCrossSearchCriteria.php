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
 * @return \TTest_Tracker_CrossSearch_CriteriaBuilder
 */
function aCrossSearchCriteria() {
    return new Test_Tracker_CrossSearch_CriteriaBuilder();
}
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';
class Test_Tracker_CrossSearch_CriteriaBuilder {

    public function __construct() {
        $this->sharedFieldsCriteria = array();
        $this->semanticCriteria     = array('title' => '', 'status' => 'any');
        $this->artifact_ids         = array();
    }
    
    /**
     * @return \Tracker_CrossSearch_Query
     */
    public function build() {
        return new Tracker_CrossSearch_Query($this->sharedFieldsCriteria, $this->semanticCriteria, $this->artifact_ids);
    }

    /**
     * @return \Test_Tracker_CrossSearch_CriteriaBuilder
     */
    public function withSharedFieldsCriteria($criteria) {
        $this->sharedFieldsCriteria = $criteria;
        return $this;
    }

    /**
     * @return \Test_Tracker_CrossSearch_CriteriaBuilder
     */
    public function withSemanticCriteria($semantic_criteria) {
        $this->semanticCriteria = $semantic_criteria;
        return $this;
    }
    
    public function forOpenItems() {
        $this->semanticCriteria['status'] = Tracker_CrossSearch_SemanticStatusReportField::STATUS_OPEN;
        return $this;
    }

    public function withArtifactIds($artifact_ids) {
        $this->artifact_ids = $artifact_ids;
        return $this;
    }
    
}

?>
