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
require_once TRACKER_BASE_DIR .'/Tracker/TreeNode/CardPresenterNode.class.php';

class Planning_ItemCardPresenterCallback implements TreeNodeCallback {

    /**
     * @var Planning
     */
    private $planning;

    /**
     * @var string the css class name
     */
    private $classname;

    /**
     * @var Tracker_CardFields
     */
    private $card_fields;

    /**
     * @var PFUser
     */
    private $user;

    public function __construct(Planning $planning, Tracker_CardFields $card_fields, PFUser $user, $classname) {
        $this->planning    = $planning;
        $this->card_fields = $card_fields;
        $this->classname   = $classname;
        $this->user        = $user;
    }

    /**
     * Makes a CardPresenterNode out of $node if $node contains an artifact
     *
     * TODO something is wrong since we return different types here
     * When on the left side of the planning, the top node is just
     * a node holding the other nodes, and we cant use an array of nodes because
     * there are card-actions available for it...
     *
     * @param TreeNode $node
     *
     * @return \Tracker_TreeNode_CardPresenterNode or \TreeNode
     */
    public function apply(TreeNode $node) {
        $artifact = $node->getObject();

        if ($artifact) {
            $color               = $artifact->getCardAccentColor($this->user);
            $parent              = $artifact->getParent($this->user);
            $planning_item       = new Planning_Item($artifact, $this->planning, $parent);
            $display_preferences = new Planning_CardDisplayPreferences();
            $presenter           = new Planning_ItemPresenter($planning_item, $this->card_fields, $display_preferences, $color, $this->classname);
            $presenter_node      = new Tracker_TreeNode_CardPresenterNode($node, $presenter);
            return $presenter_node;
        }
        return $node;
    }
}
?>
