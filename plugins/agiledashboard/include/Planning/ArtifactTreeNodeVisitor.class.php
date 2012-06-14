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
require_once 'Item.class.php';
require_once 'ItemPresenter.class.php';

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
    
    public function __construct(Planning                $planning,
                                Tracker_ArtifactFactory $artifact_factory,
                                                        $classname) {
        $this->planning         = $planning;
        $this->artifact_factory = $artifact_factory;
        $this->classname        = $classname;
    }
    
    /**
     * @param string $classname The css classname to inject in TreeNode
     *
     * @return Planning_ArtifactTreeNodeVisitor
     */
    public static function build(Planning $planning, $classname) {
        $artifact_factory = Tracker_ArtifactFactory::instance();
        
        return new Planning_ArtifactTreeNodeVisitor($planning, $artifact_factory, $classname);
    }

    public function visit(TreeNode $node) {
        $this->decorate($node);
        $this->visitChildren($node);
    }
    
    private function decorate(TreeNode $node) {
        $artifact = $this->getArtifact($node);
        
        if ($artifact) {
            $planning_item = new Planning_Item($artifact, $this->planning);
            $presenter     = new Planning_ItemPresenter($planning_item, $this->classname);
            
            $node->setObject($presenter);
        }
    }
    
    private function getArtifact(TreeNode $node) {
        $row = $node->getData();
        return $this->artifact_factory->getArtifactById($row['id']);
    }
    
    private function visitChildren(TreeNode $node) {
        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }
}

?>
