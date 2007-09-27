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
 * Link datas, views and actions.
 *
 * This is a part of lite Model/View/Controler design pattern.
 *
 * @package   codex-mvc
 * @copyright STMicroelectronics, 2005
 * @author    Manuel Vacelet <manuel.vacelet-abecedaire@st.com>
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class Controler {
  /* protected */ var $gid;
  /* protected */ var $view;
  /* protected */ var $action;
  /* protected */ var $_viewParams   = array();
  /* protected */ var $_actionParams = array();

  function request() {
  }

  function viewsManagement() {
    $className = get_class($this).'Views';
    $wv = new $className($this, $this->gid, $this->view, $this->_viewParams);
    return $wv->display($this->view);
  }

  function actionsManagement() {
    $className = get_class($this).'Actions';
    $wa = new $className($this, $this->gid);
    $wa->process($this->action, $this->_actionParams);
  }

  function process() {
    $this->request();

    if($this->action)
      $this->actionsManagement();
    
    return $this->viewsManagement();
  }

}

?>