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
 * TreeNode containing a Tracker_CardPresenter
 */
class Tracker_TreeNode_CardPresenterNode extends NodeDuplicator
{

    /**
     * @var Tracker_CardPresenter
     */
    private $presenter;

    public function __construct(TreeNode $node, Tracker_CardPresenter $presenter)
    {
        parent::__construct($node);
        $this->presenter = $presenter;
    }

    /**
     * @return Tracker_CardPresenter
     */
    public function getCardPresenter()
    {
        return $this->presenter;
    }
}
