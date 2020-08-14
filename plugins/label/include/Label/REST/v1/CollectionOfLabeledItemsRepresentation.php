<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

/**
 * @psalm-immutable
 */
class CollectionOfLabeledItemsRepresentation
{
    public const ROUTE = 'labeled_items';

    /**
     * @var LabeledItemRepresentation[]
     */
    public $labeled_items;

    /**
     * @var bool
     */
    public $are_there_items_user_cannot_see;

    /**
     * @param LabeledItemRepresentation[] $labeled_items
     */
    private function __construct(array $labeled_items, bool $are_there_items_user_cannot_see)
    {
        $this->labeled_items                   = $labeled_items;
        $this->are_there_items_user_cannot_see = $are_there_items_user_cannot_see;
    }

    public static function build(LabeledItemCollection $labeled_items): self
    {
        $labeled_item_representations = [];
        foreach ($labeled_items->getItems() as $item) {
            $representation = new LabeledItemRepresentation($item);

            $labeled_item_representations[] = $representation;
        }

        return new self(
            $labeled_item_representations,
            $labeled_items->areThereItemsUserCannotSee()
        );
    }
}
