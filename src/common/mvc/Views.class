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

/**
 * Representation of model.
 *
 * This is a part of lite Model/View/Controler design pattern.
 *
 * @package   codex-mvc
 * @copyright STMicroelectronics, 2005
 * @author    Manuel Vacelet <manuel.vacelet-abecedaire@st.com>
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class Views {
  /* protected array */  var $html_params; 
  /* protected string */ var $view; 
  /* protected Controler */ var $_controler;
  
  function View(&$controler, $view = null, $params = array()) {
      $this->_controler =& $controler;
      $this->view=$view;
  }
  
    function getControler() {
        return $this->_controler;
    }
    

  function header() {
    site_project_header($this->html_params);
  }

  function footer() {
    site_project_footer($this->html_params);
  }

  function main() {
    
  }  

  function display($view='') {
    $this->header();
    if(!empty($view)) $this->$view();
    $this->footer();
  }
}
?>