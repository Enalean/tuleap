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

require_once 'common/TreeNode/TreeNodeCallback.class.php';

/**
 * Creates a CardPresenter given a TreeNode with Artifact
 */
class Cardwall_CreateCardPresenterCallback implements TreeNodeCallback {

    private $card_fields;

    /**
     * @var PFUser
     */
    private $user;

    public function __construct($card_fields, PFUser $user) {
         $this->card_fields = $card_fields;
         $this->user        = $user;
    }

    /**
     * @see TreeNodeCallback
     *
     * @param TreeNode $node
     * @return \Tracker_TreeNode_CardPresenterNode
     */
    public function apply(TreeNode $node) {
        if (! $node instanceof ArtifactNode) {
            return clone $node;
        }

        $artifact  = $node->getArtifact();
        $color     = $artifact->getCardAccentColor($this->user);
        $presenter = new Cardwall_CardPresenter($artifact, $this->card_fields, $color, $artifact->getParent($this->user));
        $new_node  = new Tracker_TreeNode_CardPresenterNode($node, $presenter);
        return $new_node;
    }
}

?>