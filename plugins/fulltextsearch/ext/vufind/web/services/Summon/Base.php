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
 
require_once 'Action.php';
require_once 'sys/Summon.php';

class Base extends Action
{
    protected $searchObject;
    
    function __construct()
    {
        global $interface;
        $interface->assign('currentTab', 'Summon');
        
        // Send Summon search types to the template so the basic search box can
        // function on all pages of the Summon UI.
        $this->searchObject = SearchObjectFactory::initSearchObject('Summon');
        $interface->assign('summonSearchTypes', $this->searchObject->getBasicTypes());
    }
}
?>
