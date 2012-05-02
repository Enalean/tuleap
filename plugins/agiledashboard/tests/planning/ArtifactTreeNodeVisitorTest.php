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

require_once dirname(__FILE__) .'/../../../tracker/include/constants.php';
require_once dirname(__FILE__) .'/../../include/Planning/ArtifactTreeNodeVisitor.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/Tracker.class.php';

class Planning_ArtifactTreeNodeVisitorTest extends TuleapTestCase {
    public function itWrapsAnArtifactInATreeNode() {
        $tracker           = mock('Tracker');
        $children_trackers = array(mock('Tracker'), mock('Tracker'));
        
        $hierarchy_factory = mock('Tracker_Hierarchy_HierarchicalTrackerFactory');
        stub($hierarchy_factory)->getChildren($tracker)->returns($children_trackers);
        
        $artifact = mock('Tracker_Artifact');
        stub($artifact)->getId()->returns(123);
        stub($artifact)->getTitle()->returns('Foo');
        stub($artifact)->getUri()->returns('/bar');
        stub($artifact)->getXRef()->returns('art #123');
        stub($artifact)->getTracker()->returns($tracker);
        
        $artifact_factory = mock('Tracker_ArtifactFactory');
        stub($artifact_factory)->getArtifactById(123)->returns($artifact);
        
        $node    = new TreeNode(array('id' => 123));
        $visitor = new Planning_ArtifactTreeNodeVisitor($artifact_factory, $hierarchy_factory, 'baz', '');
        
        $visitor->visit($node);
        
        $data = $node->getData();
        $this->assertEqual($data['id'],                   123);
        $this->assertEqual($data['title'],                'Foo');
        $this->assertEqual($data['uri'],                  '/bar');
        $this->assertEqual($data['xref'],                 'art #123');
        $this->assertEqual($data['class'],                'baz');
        $this->assertEqual($data['allowedChildrenTypes'], $children_trackers);
    }
}

?>
