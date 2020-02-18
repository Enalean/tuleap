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
class Docman_SubItemsRemovalVisitor /* implements Visitor */
{

    public function visitFolder(&$item, $params = array())
    {
        $item->removeAllItems();
    }
    public function visitDocument(&$item, $params = array())
    {
        //Do nothing
    }
    public function visitWiki(&$item, $params = array())
    {
        $this->visitDocument($item, $params);
    }
    public function visitLink(&$item, $params = array())
    {
        $this->visitDocument($item, $params);
    }
    public function visitFile(&$item, $params = array())
    {
        $this->visitDocument($item, $params);
    }
    public function visitEmbeddedFile(&$item, $params = array())
    {
        return $this->visitFile($item, $params);
    }

    public function visitEmpty(&$item, $params = array())
    {
        $this->visitDocument($item, $params);
    }
}
