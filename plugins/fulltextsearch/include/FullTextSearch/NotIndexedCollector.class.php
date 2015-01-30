<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class FullTextSearch_NotIndexedCollector {
    private $at_least_one_indexed = false;
    private $item_ids             = array();

    public function isAtLeastOneIndexed() {
        return $this->at_least_one_indexed;
    }

    public function setAtLeastOneIndexed() {
        $this->at_least_one_indexed = true;
    }

    public function isAtLeastOneNotIndexed() {
        return count($this->getIds()) > 0;
    }

    public function getIds() {
        return $this->item_ids;
    }

    public function add(Docman_Item $item) {
        $this->item_ids[] = $item->getId();
    }
}
