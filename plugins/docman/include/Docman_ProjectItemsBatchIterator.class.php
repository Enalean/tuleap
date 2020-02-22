<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Docman_ProjectItemsBatchIterator
{

    public const ITEMS_PER_BATCH = 1000;

    private $batches_processed;

    /* Docman_ItemFactory */
    private $item_factory;

    public function __construct(Docman_ItemFactory $item_factory)
    {
        $this->item_factory = $item_factory;
    }

    /**
     * @return Docman_Item[]
     */
    public function next()
    {
        $this->batches_processed++;

        return $this->current();
    }

    /**
     * @return Docman_Item[]
     */
    public function current()
    {
        $offset = max(array(self::ITEMS_PER_BATCH * $this->batches_processed, 0));
        $limit  = self::ITEMS_PER_BATCH;

        return $this->item_factory->searchPaginatedWithVersionByGroupId($limit, $offset);
    }

    public function rewind()
    {
        $this->batches_processed = -1;
    }
}
