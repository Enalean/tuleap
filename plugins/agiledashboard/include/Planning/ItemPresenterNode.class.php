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

/**
 * TreeNode containing a Planning_ItemPresenter
 */
class Planning_ItemPresenterNode extends TreeNode {

    /**
     * @var Planning_ItemPresenter
     */
    private $presenter;
    
    public function __construct(TreeNode $node, Planning_ItemPresenter $presenter) {
        parent::__construct($node->getData(), $node->getId());
        $this->setChildren($node->getChildren());
        $this->setObject($node->getObject());
        $this->presenter = $presenter;
    }

    public static function build(TreeNode $node, Planning_ItemPresenter $presenter) {
        return new Planning_ItemPresenterNode($node, $presenter);
    }

    /**
     * @return Planning_ItemPresenter
     */
    public function getPlanningItemPresenter() {
        return $this->presenter;
    }
}

?>
