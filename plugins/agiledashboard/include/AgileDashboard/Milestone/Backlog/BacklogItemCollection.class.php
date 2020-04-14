<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class AgileDashboard_Milestone_Backlog_BacklogItemCollection implements
    AgileDashboard_Milestone_Backlog_IBacklogItemCollection,
    Iterator,
    Countable
{
    /** @var AgileDashboard_Milestone_Backlog_IBacklogItem[] */
    private $rows = array();

    /** @var int[] */
    private $index = array();

    /** @var string */
    private $parent_item_name = '';

    /** @var Int */
    private $total_available_size;

    public function getParentItemName()
    {
        return $this->parent_item_name;
    }

    public function setParentItemName($name)
    {
        $this->parent_item_name = $name;
    }

    public function push(AgileDashboard_Milestone_Backlog_IBacklogItem $item)
    {
        $this->rows[] = $item;
        $this->index[$item->id()] = true;
    }

    public function containsId($id)
    {
        return isset($this->index[$id]);
    }

    public function current()
    {
        return current($this->rows);
    }

    public function key()
    {
        return key($this->rows);
    }

    public function next()
    {
        next($this->rows);
    }

    public function rewind()
    {
        reset($this->rows);
    }

    public function valid()
    {
        return current($this->rows) !== false;
    }

    public function count()
    {
        return count($this->rows);
    }

    public function getTotalAvaialableSize()
    {
        return $this->total_available_size;
    }

    public function setTotalAvaialableSize($size)
    {
        $this->total_available_size = (int) $size;
    }

    public function getItemIds()
    {
        return array_keys($this->index);
    }
}
