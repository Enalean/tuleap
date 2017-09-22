<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Label\REST\v1;

use Tuleap\Label\LabeledItemCollection;

class CollectionOfLabeledItemsRepresentation
{
    const ROUTE = 'labeled_items';

    /**
     * @var LabeledItemRepresentation[]
     */
    public $labeled_items;

    /**
     * @var bool
     */
    public $are_there_items_user_cannot_see;

    public function build(LabeledItemCollection $labeled_items)
    {
        $this->labeled_items = array();
        foreach ($labeled_items->getItems() as $item) {
            $representation = new LabeledItemRepresentation();
            $representation->build($item);

            $this->labeled_items[] = $representation;
        }

        $this->are_there_items_user_cannot_see = $labeled_items->areThereItemsUserCannotSee();
    }
}
