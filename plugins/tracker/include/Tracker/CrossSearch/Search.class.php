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

require_once 'SharedFieldFactory.class.php';
require_once 'SearchDao.class.php';
require_once 'ResultSorter.class.php';
require_once dirname(__FILE__).'/../FormElement/Tracker_FormElementFactory.class.php';
require_once dirname(__FILE__).'/../Hierarchy/Hierarchy.class.php';

/**
 * The cross-tracker search model.
 * 
 * See: getHierarchicallySortedArtifacts()
 */
class Tracker_CrossSearch_Search {
    
    /**
     * @var Tracker_CrossSearch_SharedFieldFactory
     */
    private $shared_field_factory;
    
    /**
     * @var Tracker_CrossSearch_SearchDao
     
     */
    private $dao;
    
    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    
    public function __construct(Tracker_CrossSearch_SharedFieldFactory $shared_field_factory,
                                Tracker_CrossSearch_SearchDao          $dao,
                                Tracker_HierarchyFactory               $hierarchy_factory) {
        $this->hierarchy_factory    = $hierarchy_factory;
        $this->shared_field_factory = $shared_field_factory;
        $this->dao                  = $dao;
    }
    
    /**
     * Retrieve a tree of artifacts matching the given search criteria.
     * 
     * The artifacts tree matches the trackers hierarchy definition.
     * 
     * @param array                        $tracker_ids            The trackers to retrieve artifacts from.
     * @param Tracker_CrossSearch_Query $criteria               The criteria that artifacts should match (e.g. title).
     * @param array                        $excluded_artifact_ids  Some (optional) artifacts to exclude.
     * @return TreeNode
     */
    public function getHierarchicallySortedArtifacts($tracker_ids, Tracker_CrossSearch_Query $criteria, $excluded_artifact_ids = array()) {
        $hierarchy = $this->hierarchy_factory->getHierarchy($tracker_ids);
        return $this->getMatchingArtifacts($tracker_ids, $hierarchy, $criteria, $excluded_artifact_ids);
    }
    
    public function getMatchingArtifacts(array $tracker_ids, Tracker_Hierarchy $hierarchy, Tracker_CrossSearch_Query $criteria, $excluded_artifact_ids = array()) {
        $shared_fields   = $this->shared_field_factory->getSharedFields($criteria->getSharedFields());
        $semantic_fields = $criteria->getSemanticCriteria();
        
        $artifacts = $this->dao->searchMatchingArtifacts($tracker_ids, $shared_fields, $semantic_fields, $excluded_artifact_ids);
        
        $result_sorter = new ResultSorter();
        return $result_sorter->sortResults($artifacts, $tracker_ids, $hierarchy);
    }
    
}
?>
