<?php
/* 
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Modify model following given actions.
 *
 * This is a part of lite Model/View/Controler design pattern.
 *
 * @package Codendi-mvc
 * @copyright STMicroelectronics, 2005
 * @author    Manuel Vacelet <manuel.vacelet-abecedaire@st.com>
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class Actions {
    
    var $_controler;
    function Actions(&$controler) {
        $this->_controler =& $controler;
    }
    
    function getControler() {
        return $this->_controler;
    }
    
  function check() {
    return true;
  }

  function process($action, $params = array()) {
    if($this->check()) {
      $this->$action($params);
    }
  }
}
?>