<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
class Docman_ExpandAllHierarchyVisitor /* implements Visitor */
{
    public function visitFolder(&$item, $params = [])
    {
        if ($item->getParentId()) {
            //No need to expand root
            $params['folderFactory']->expand($item);
        }
        $items = $item->getAllItems();
        if ($items->size()) {
            $it = $items->iterator();
            while ($it->valid()) {
                $o = $it->current();
                $o->accept($this, $params);
                $it->next();
            }
        }
    }
    public function visitDocument(&$item, $params = [])
    {
        //Do nothing
    }
    public function visitWiki(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
    public function visitLink(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
    public function visitFile(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
    public function visitEmbeddedFile(&$item, $params = [])
    {
        return $this->visitFile($item, $params);
    }

    public function visitEmpty(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
}
