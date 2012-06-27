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

require_once dirname(__FILE__).'/../include/ColumnPresenterCallback.class.php';

class ColumnPresenterCallbackTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $field = mock('Tracker_FormElement_Field_MultiselectBox');
        $artifact = mock('Tracker_Artifact');

        $field_retriever = stub('Tracker_Artifact_Field_Retriever')->getField($artifact)->returns($field);
        $this->callback = new ColumnPresenterCallback($field_retriever);
        
        $this->node     = aNode()->withId(4444)->build();
        $presenter =  stub('Cardwall_CardPresenter')->getArtifact()->returns($artifact);
        $this->presenter_node     = new Tracker_TreeNode_CardPresenterNode($this->node, $presenter);
    }
    
    public function itJustClonesTheNodeIfItIsNotAPresenterNode() {
        $result = $this->callback->apply($this->node);
        $this->assertIdentical($this->node, $result);
    }
    
    public function itCreatesAColumnPresenterNode() {
        $result = $this->callback->apply($this->presenter_node);
        $this->assertIsA($result, 'Cardwall_ColumnPresenterNode');
    }
    
    public function itHasTheSameIdAsTheGivenNode() {
        $result = $this->callback->apply($this->presenter_node);
        $this->assertEqual($this->node->getId(), $result->getId());
    }
    
    public function itHasAColumnPresenterWithASemanticStatusFieldId() {
        $field = stub('Tracker_FormElement_Field_MultiselectBox')->getId()->returns(77777);
        
        $artifact = mock('Tracker_Artifact');
        $artifact_field_retriever = stub('Tracker_Artifact_Field_Retriever')->getField($artifact)->returns($field);
        
        $presenter = stub('Cardwall_CardPresenter')->getArtifact()->returns($artifact);
        $presenter_node     = new Tracker_TreeNode_CardPresenterNode($this->node, $presenter);

        $this->callback = new ColumnPresenterCallback($artifact_field_retriever);
        $result = $this->callback->apply($presenter_node);
        
        $this->assertEqual(77777, $result->getColumnPresenter()->getCardFieldId());
    }
}

?>
