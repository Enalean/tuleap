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
class ColumnPresenterCallback implements TreeNodeCallback {

    /**
     * @var Tracker_Artifact_Field_Retriever
     */
    private $field_retriever;
    
    public function __construct(Tracker_Artifact_Field_Retriever $retriever = null) {
        $this->field_retriever = $retriever;
    }
    
    public function apply(TreeNode $node) {
        if ($node instanceof Tracker_TreeNode_CardPresenterNode) {
            $artifact = $node->getCardPresenter()->getArtifact();
            $field = $this->field_retriever->getField($artifact);
            if ($field) {
                $card_field_id = $field->getId();
            } else {
                $card_field_id = 0;
            }
            
            $presenter = new ColumnPresenter($node->getCardPresenter(), $card_field_id);
            return new Cardwall_ColumnPresenterNode($node, $presenter);
        }
        return clone $node;
    }
    
}

interface Tracker_Artifact_Field_Retriever {
    
    function getField(Tracker_Artifact $artifact);
}

class Tracker_Artifact_Semantic_Status_Field_Retriever implements Tracker_Artifact_Field_Retriever {

    /**
     * @return Tracker_FormElement_Field_Selectbox
     */
    public function getField(Tracker_Artifact $artifact) {
        $tracker = $artifact->getTracker();
        return Tracker_Semantic_StatusFactory::instance()->getByTracker($tracker)->getField();
    }
}

class Tracker_Artifact_Custom_Field_Retriever implements Tracker_Artifact_Field_Retriever {

    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    private $field;

    public function __construct(Tracker_FormElement_Field_Selectbox $field = null) {
        $this->field = $field;
    }

    /**
     * @param Tracker_Artifact $artifact is ignored!
     * @return Tracker_FormElement_Field_Selectbox
     */
    public function getField(Tracker_Artifact $artifact) {
        return $this->field;
    }
}

?>
