<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once 'Interface.php';

class Sample implements DriverInterface
{
    public function getStatus($id)
    {
        $holding[] = array('availability' => 1,
                           'status' => 'Available',
                           'location' => '3rd Floor Main Library',
                           'reserve' => 'No',
                           'callnumber' => 'A1234.567',
                           'duedate' => '',
                           'number' => 1);
        return $holding;
    }

    public function getStatuses($ids)
    {
        $items = array();
        foreach ($ids as $id) {
            $holding = array();
            $holding[] = array('availability' => 1,
                               'id' => $id,
                               'status' => 'Available',
                               'location' => '3rd Floor Main Library',
                               'reserve' => 'No',
                               'callnumber' => 'A1234.567',
                               'duedate' => '',
                               'number' => 1);
            $items[] = $holding;
        }
        return $items;
    }

    public function getHolding($id)
    {
        return $this->getStatus($id);
    }

    public function getPurchaseHistory($id)
    {
        return array();
    }

    public function getNewItems($page, $limit, $daysOld, $fundId = null)
    {
        return array('count' => 0, 'results' => array());
    }

    function findReserves($course, $inst, $dept)
    {
        return array();
    }
    function patronLogin($username, $password)
    {
        return null;
    }
}
?>