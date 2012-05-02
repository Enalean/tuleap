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

require_once 'ArtifactTreeNodeVisitor.class.php';

class Planning_ShowPresenter {
    
    public $__ = array(__CLASS__, '__trans');
    
    public $planning_id;
    public $planning_name;
    public $group_id;
    
    private $artifacts_to_select;
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    private $content_view;
    private $current_user;
    private $current_uri;
    
    /**
     * @var Planning
     */
    private $planning;
    
    /**
     * @var Tracker
     */
    private $planning_tracker;
    
    public function __construct(Planning $planning,
                                Tracker_CrossSearch_SearchContentView $content_view,
                                array $artifacts_to_select,
                                Tracker_Artifact $artifact = null, 
                                User $user,
                                $current_uri) {
        $this->planning            = $planning;
        $this->planning_id         = $planning->getId();
        $this->planning_name       = $planning->getName();
        $this->planning_tracker    = $planning->getPlanningTracker();
        $this->artifact            = $artifact;
        $this->artifacts_to_select = $artifacts_to_select;
        $this->content_view        = $content_view;
        $this->group_id            = $planning->getGroupId();
        $this->current_user        = $user;
        $this->current_uri         = $current_uri;
    }
    
    
    /**
     * @return bool
     */
    public function hasArtifact() {
        return $this->artifact !== null;
    }
    
    /**
     * @return TreeNode
     */
    public function getDestination($child_depth = 1) {
        $destination = null;
        if ($this->artifact) {
            $destination = $this->getTreeNode($child_depth);
            Planning_ArtifactTreeNodeVisitor::build('planning-draggable-alreadyplanned', $this->current_uri)->visit($destination);
        }
        return $destination;
    }
    
    /**
     * @return TreeNode
     */
    private function getTreeNode($child_depth) {
        $id          = $this->artifact->getId();
        $parent_node = new TreeNode(array('id' => $id, 'allowedChildrenTypes' => $this->planning->getBacklogTrackers()));
        $parent_node->setId($id);
        $this->addChildItem($this->artifact, $parent_node, $child_depth);
        return $parent_node;
    }
    
    private function addChildItem($artifact, $parent_node, $child_depth = 0) {
        $linked_items = $artifact->getUniqueLinkedArtifacts($this->current_user);
        if (! $linked_items) {
            return false;
        }
        foreach ($linked_items as $item) {
            $node = new TreeNode(
                array(
                    'id' => $item->getId(),
                )
            );
            $node->setId($item->getId());
            if ($child_depth > 0 ) {
                $this->addChildItem($item, $node, $child_depth - 1);
            }
            $parent_node->addChild($node);
        }
    }
    
    /**
     * @return string html
     */
    public function fetchSearchContent() {
        return $this->content_view->fetch();
    }
    
    
    /**
     * @return string
     */
    public function pleaseChoose() {
        return $GLOBALS['Language']->getText('global', 'please_choose_dashed');
    }
    
    
    /**
     * @return array of (id, title, selected)
     */
    public function artifactsToSelect() {
        $hp             = Codendi_HTMLPurifier::instance();
        $artifacts_data = array();
        $selected_id    = $this->artifact ? $this->artifact->getId() : null;
        foreach ($this->artifacts_to_select as $artifact) {
            $artifacts_data[] = array(
                'id'       => $artifact->getId(),
                'title'    => $hp->purify($artifact->getTitle()),
                'selected' => ($artifact->getId() == $selected_id) ? 'selected="selected"' : '',
            );
        }
        return $artifacts_data;
    }
    
    /**
     * @return string
     */
    public function destinationHelp() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_destination_help');
    }
    
    /**
     * @return string
     */
    public function getDestinationDroppableClass() {
        if ($this->canDrop()) {
            return 'planning-droppable';
        }
        return false;
    }
    
    private function getArtifactCreationLabel(Tracker $tracker) {
        $new       = $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_artifact_new');
        $item_name = $tracker->getItemName();
        
        return "$new $item_name";
    }
    
    /**
     * @return string
     */
    public function getPlanningTrackerArtifactCreationLabel() {
        return $this->getArtifactCreationLabel($this->planning_tracker);
    }
    
    /**
     * @return string
     */
    public function getPlanningTrackerArtifactCreationUrl() {
        $tracker_id = $this->planning_tracker->getId();
        $return_url = urlencode($this->getCurrentUri());
        
        return TRACKER_BASE_URL."/?tracker=$tracker_id&func=new-artifact&return_to=$return_url";
    }
    
    /**
     * @return bool
     */
    public function canDrop() {
        if ($this->artifact) {
            $art_link_field = $this->artifact->getAnArtifactLinkField($this->current_user);
            if ($art_link_field && $art_link_field->userCanUpdate($this->current_user)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * @return string html
     */
    public function errorCantDrop() {
        if ($this->canDrop()) {
            return false;
        }
        return '<div class="feedback_warning">'. $GLOBALS['Language']->getText('plugin_tracker', 'must_have_artifact_link_field') .'</div>';
    }
    
    /**
     * @return string
     */
    public function getCurrentUri() {
        return $this->current_uri;
    }
    
    /**
     * @return string
     */
    public function getCurrentUrlEncoded() {
        return urlencode($this->current_uri);
    }
    
    /**
     * @return string
     */
    public function createNewItemToPlan() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'create_new_item_to_plan');
    }
    
    /**
     * @return string
     */
    public function editLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'edit_item');
    }
    
    /**
     * @return string
     */
    public function __trans($text) {
        $args = explode('|', $text);
        $secondary_key = array_shift($args);
        return $GLOBALS['Language']->getText('plugin_agiledashboard', $secondary_key, $args);
    }
}

?>
