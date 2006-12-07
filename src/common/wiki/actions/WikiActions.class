<?php
/* 
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
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
 */

require_once('common/mvc/Actions.class.php');
require_once(dirname(__FILE__).'/../lib/WikiPageWrapper.class.php');

/**
 *
 * @package WikiService
 * @copyright STMicroelectronics, 2005
 * @author Manuel Vacelet <manuel.vacelet-abecedaire@st.com>
 * @license http://opensource.org/licenses/gpl-license.php GPL
 */
class WikiActions extends Actions {
  
    function WikiActions(&$controler) {
        $this->Actions($controler);
    }
    
  function add_temp_page() {
    /* ADD TEST TO NOT ADD A ALREADY existing PAge */
    $wpw = new WikiPageWrapper($this->gid);
    $wpw->addNewProjectPage($_POST['name']);
  }

}

?>