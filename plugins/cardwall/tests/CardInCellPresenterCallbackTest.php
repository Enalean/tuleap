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

require_once dirname(__FILE__) .'/bootstrap.php';
require_once dirname(__FILE__).'/../../../tests/simpletest/common/include/builders/aTreeNode.php';

class CardInCellPresenterCallbackTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $tracker        = mock('Tracker');
        $this->field_id = 77777;
        $this->field    = stub('Tracker_FormElement_Field_MultiselectBox')->getId()->returns($this->field_id);
        $this->artifact = aMockArtifact()->withId(4444)->withTracker($tracker)->build();
        stub($this->artifact)->getAllowedChildrenTypesForUser()->returns(array());

        $this->node           = aNode()->withId(4444)->build();
        $this->card_presenter = stub('Cardwall_CardPresenter')->getArtifact()->returns($this->artifact);
        $this->artifact_node  = new ArtifactNode($this->artifact);

        $this->field_provider = stub('Cardwall_FieldProviders_IProvideFieldGivenAnArtifact')->getField($tracker)->returns($this->field);

        $card_in_cell_presenter_factory = new Cardwall_CardInCellPresenterFactory($this->field_provider, new Cardwall_MappingCollection());

        $node_factory = new Cardwall_CardInCellPresenterNodeFactory(
            $card_in_cell_presenter_factory,
            mock('Cardwall_CardFields'),
            mock('Cardwall_UserPreferences_UserPreferencesDisplayUser'),
            mock('PFUser')
        );

        $this->callback = new Cardwall_CardInCellPresenterCallback($node_factory);
    }

    public function itJustClonesTheNodeIfItIsNotAnArtifactNode() {
        $cardincell_presenter_node = $this->callback->apply($this->node);
        $this->assertIdentical($this->node, $cardincell_presenter_node);
    }

    public function itCreatesACardInCellPresenterNode() {
        $cardincell_presenter_node = $this->callback->apply($this->artifact_node);
        $this->assertIsA($cardincell_presenter_node, 'Cardwall_CardInCellPresenterNode');
    }

    public function itHasTheSameIdAsTheGivenNode() {
        $cardincell_presenter_node = $this->callback->apply($this->artifact_node);
        $this->assertEqual($this->node->getId(), $cardincell_presenter_node->getId());
    }
}

?>
