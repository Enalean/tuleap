<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('service.php');

require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');


class DocmanWatermarkActions extends Actions {
    
    var $event_manager;
    
    public function DocmanWatermarkActions(&$controler, $view=null) {
        parent::Actions($controler);
        $this->event_manager =& $this->_getEventManager();
    }
    
    public function &_getEventManager() {
        $em =& EventManager::instance();
        return $em;
    }
    
    public function setup_confidentiality_field() {
        $f_confid = $this->_controler->_actionParams['f_confid'];
        $mf       = new DocmanWatermarkMetadataFactory();
        
    }
    
    public function setup_confidentiality_values() {
        $values = $this->_controler->_actionParams['values'];
        $mvf    = new DocmanWatermarkMetadataValueFactory();
        
    }
    
    
    
}

?>
