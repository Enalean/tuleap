<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_SOAP
*/


class Docman_View_SOAP_List {
    
    function display($params) {
        
        $result = array();
        $itemFactory = new Docman_ItemFactory($params['group_id']);
        $itemTree =& $itemFactory->getItemSubTree($params['item']->getId(), array('user' => $params['user']));
        
        $items = $itemTree->getAllItems();
        $nb = $items->size();
        if ($nb) { 
            $it =& $items->iterator();
            while($it->valid()) {
                $o =& $it->current();
                $result[] = $o->toRow();
                $it->next();
            }
        }
        return $result;
    }
}

?>
