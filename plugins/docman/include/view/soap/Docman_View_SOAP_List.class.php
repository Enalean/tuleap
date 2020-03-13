<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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


class Docman_View_SOAP_List
{

    public function display($params)
    {
        $result = array();
        $itemFactory = new Docman_ItemFactory($params['group_id']);
        $itemTree = $itemFactory->getItemSubTree($params['item'], $params['user']);

        $items = $itemTree->getAllItems();
        $nb = $items->size();
        if ($nb) {
            $it = $items->iterator();
            while ($it->valid()) {
                $o = $it->current();
                $result[] = $o->toRow();
                $it->next();
            }
        }
        return $result;
    }
}
