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
    

    public function DocmanWatermarkActions(&$controler, $view=null) {
        parent::Actions($controler);
    }
    
    public function setup_metadata() {
        require_once(dirname(__FILE__).'/DocmanWatermark_MetadataFactory.class.php');
        $md_id    = $this->_controler->_actionParams['md_id'];
        $group_id = $this->_controler->_actionParams['group_id'];
        $mf       = new DocmanWatermark_MetadataFactory($group_id);
        
    }
    
    public function setup_metadata_values() {
        $values = $this->_controler->_actionParams['values'];
        $mvf    = new DocmanWatermarkMetadataValueFactory();
    }
    
}

?>
