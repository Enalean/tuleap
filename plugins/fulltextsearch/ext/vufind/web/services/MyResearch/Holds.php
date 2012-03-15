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

require_once 'services/MyResearch/MyResearch.php';

class Holds extends MyResearch
{
    function launch()
    {
        global $interface;

        // Get My Holds
        if ($patron = $this->catalogLogin()) {
            if (PEAR::isError($patron))
                PEAR::raiseError($patron);
            $result = $this->catalog->getMyHolds($patron);
            if (!PEAR::isError($result)) {
                if (count($result)) {
                    $recordList = array();
                    foreach ($result as $row) {
                        $record = $this->db->getRecord($row['id']);
                        $record['createdate'] = $row['create'];
                        $record['expiredate'] = $row['expire'];
                        $recordList[] = $record;
                    }
                    $interface->assign('recordList', $recordList);
                } else {
                    $interface->assign('recordList', 'You do not have any holds');
                }
            }
        }

        $interface->setTemplate('holds.tpl');
        $interface->setPageTitle('My Holds');
        $interface->display('layout.tpl');
    }
    
}

?>