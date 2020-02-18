<?php
/*
 * Copyright (c) STMicroelectronics, 2009
 * Originally written by Manuel VACELET, STMicroelectronics, 2009
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Docman_View_SOAP_Search
{

    public function display($params)
    {
        $itemFactory = new Docman_ItemFactory($params['group_id']);
        $nbItemsFound = 0;
        $itemIterator = $itemFactory->getItemList(
            $params['item']->getId(),
            $nbItemsFound,
            array('user' => $params['user'],
                                                        'ignore_collapse' => true,
                                                        'ignore_obsolete' => true,
                                                        'filter' => $params['filter'],
            'getall' => true)
        );

        $result = array();
        foreach ($itemIterator as $item) {
            $result[] = $item->toRow();
        }
        return $result;
    }
}
