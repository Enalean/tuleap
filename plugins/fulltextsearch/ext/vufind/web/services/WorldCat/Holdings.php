<?php
/**
 *
 * Copyright (C) Andrew Nagy 2009.
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
 
require_once 'Record.php';

class Holdings extends Record
{
    function launch() 
    {
        global $interface;    
    
        if (!$interface->is_cached($this->cacheId)) {
            $holdings = $this->worldcat->getHoldings($this->id);

            // Normalize holding to array to compensate for XML_Unserializer
            // inconsistencies between single element and multiple elements.
            if (isset($holdings['holding']['institutionIdentifier'])) {
                $holdings['holding'] = array($holdings['holding']);
            }
            $interface->assign('holdings', $holdings['holding']);

            $interface->assign('subTemplate', 'view-holdings.tpl');
            $interface->setTemplate('view.tpl');
        }

        // Display Page
        $interface->display('layout.tpl', $this->cacheId);
    }
}
?>