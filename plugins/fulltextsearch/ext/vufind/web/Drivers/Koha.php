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

class Koha implements DriverInterface
{
    private $db;

    function __construct()
    {
        global $configArray;
        
        $this->db = new Zebra($configArray['Index']['url']);
    }

    public function getHolding($id)
    {
        $result = $this->db->search("rec.id=$id", null, null, 1);

        if (isset($result['record']['holdings']['barcode'])) {
            $holdingList = array($result['record']['holding']);
        } else {
            $holdingList = $result['record']['holdings'];
        }


        $holding = array();
        foreach ($holdingList as $item) {
            if ($item['onloan']) {
                $duedate = $item['onloan'];
                $availability = 0;
                $status = 'Checked Out';
            } elseif ($item['notforloan']) {
                $duedate = '';
                $availability = 0;
                $status = 'Unavailable';
            } elseif ($item['damaged']) {
                $duedate = '';
                $availability = 0;
                $status = 'Damaged';
            } elseif ($item['itemlost']) {
                $duedate = '';
                $availability = 0;
                $status = 'Lost';
            } elseif ($item['withdrawn']) {
                $duedate = '';
                $availability = 0;
                $status = 'Withdrawn';
            } else {
                $duedate = '';
                $availability = 1;
                $status = 'Available';
            }
            $holding[] = array('id' => $id,
                               'availability' => $availability,
                               'status' => $status,
                               'duedate' => $duedate,
                               'location' => ($item['location']) ? $item['location'] : 'Unknown',
                               'reserve' => ($item['reserves']) ? $item['reserves'] : 'No',
                               'callnumber' => ($item['itemcallnumber']) ? $item['itemcallnumber'] : 'Unknown',
                               'number' => $item['copynumber'],
                               'branch' => $item['holdingbranch']);
        }
        return $holding;
    }

    /*
    public function getHoldings($ids)
    {
        $cnt = count($ids);
        
        // Create Query
        foreach ($ids as $id) {
            $query .= "rec.id=$id";
            $i++;
            if ($i < $cnt) {
                $query .= ' OR ';
            }
        }
        $result = $this->db->search($query);

        // Build Holdings Array
        $items = array();
        foreach ($result as $record) {
        
            $recCnt = count($record['p']);

            $holding = array();
            for ($i = 0; $i < $recCnt; $i++) {
                $holding[] = array('availability' => 1,
                                   'status' => 'Available',
                                   'location' => $record['952']['c'],
                                   'reserve' => $record['952']['n'],
                                   'callnumber' => $record['952']['o'],
                                   'duedate' => '',
                                   'number' => $record['952'][$i]['t']);
            }
            $items[] = $holding;
        }
    }
    */
    
    public function getStatuses($idList)
    {
        $holdings = array();
        foreach ($idList as $id) {
            $holdings[] = $this->getStatus($id);
        }
        return $holdings;
    }
    
    public function getHolding($id)
    {
        return getStatus($id);
    }
    
    public function getPurchaseHistory($id)
    {
        return array();
    }

}
?>