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

require_once 'Planning.class.php';
require_once 'PlanningArtifact.class.php';
require_once 'PlanningArtifactPresenter.class.php';

/**
 * This visitor injects various artifact related data in a TreeNode to be used in mustache
 */
class Planning_ArtifactTreeNodeVisitor {
    
    /**
     * @var Planning
     */
    private $planning;
    
    /**
     * @var string the css class name
     */
    private $classname;
    
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    
    /**
     * @var Tracker_Hierarchy_HierarchicalTrackerFactory
     */
    private $hierarchy_factory;
    
    public function __construct(Planning                                     $planning,
                                Tracker_ArtifactFactory                      $artifact_factory,
                                Tracker_Hierarchy_HierarchicalTrackerFactory $hierarchy_factory,
                                                                             $classname) {
        $this->planning          = $planning;
        $this->artifact_factory  = $artifact_factory;
        $this->classname         = $classname;
        $this->hierarchy_factory = $hierarchy_factory;
    }
    
    /**
     * @param string $classname The css classname to inject in TreeNode
     *
     * @return Planning_ArtifactTreeNodeVisitor
     */
    public static function build(Planning $planning, $classname) {
        $artifact_factory  = Tracker_ArtifactFactory::instance();
        $hierarchy_factory = Tracker_Hierarchy_HierarchicalTrackerFactory::instance();
        
        return new Planning_ArtifactTreeNodeVisitor($planning, $artifact_factory, $hierarchy_factory, $classname);
    }

    public function visit(TreeNode $node) {
        $row      = $node->getData();
        $artifact = $this->artifact_factory->getArtifactById($row['id']);
        
        if ($artifact) {
            $planning_artifact = new PlanningArtifact($artifact, $this->planning);
            $presenter         = new PlanningArtifactPresenter($planning_artifact, $this->classname);
            
            $node->setObject($presenter);
        }
        $this->injectArtifactInChildren($node);
    }
    
    private function injectArtifactInChildren(TreeNode $node) {
        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }
}

?>
