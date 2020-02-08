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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

class OrderValidator
{
    private $index;

    public function __construct(array $index)
    {
        $this->index = $index;
    }

    /**
     * @throws IdsFromBodyAreNotUniqueException
     * @throws OrderIdOutOfBoundException
     */
    public function validate(OrderRepresentation $order)
    {
        if (! $this->areIdsUnique($order->ids)) {
            throw new IdsFromBodyAreNotUniqueException();
        }

        $this->assertIdPartOfIndex($order->compared_to);
        foreach ($order->ids as $id) {
            $this->assertIdPartOfIndex($id);
        }
    }

    private function assertIdPartOfIndex($id)
    {
        if (! isset($this->index[$id])) {
            throw new OrderIdOutOfBoundException($id);
        }
    }

    private function areIdsUnique(array $ids)
    {
        $ids_unique = array_unique($ids);
        return count($ids) == count($ids_unique);
    }
}
