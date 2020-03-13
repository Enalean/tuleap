<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


class Docman_Path
{
    public $path;
    public function __construct()
    {
        $this->path = array();
    }
    public function get(&$item)
    {
        if (!isset($this->path[$item->getId()])) {
            $this->path[$item->getId()] = '';
            if ($item->getParentId()) {
                $if = $this->_getItemFactory();
                $parent = $if->getItemFromDb($item->getParentId(), array('ignore_deleted' => true));
                if ($parent) {
                    $this->path[$item->getId()] = $this->get($parent) . '/';
                }
            }
            $this->path[$item->getId()] .= $item->getTitle();
        }
        return $this->path[$item->getId()];
    }
    public $item_factory;
    private function _getItemFactory()
    {
        if (!$this->item_factory) {
            $this->item_factory = new Docman_ItemFactory();
        }
        return $this->item_factory;
    }
}
