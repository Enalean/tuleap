<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Link datas, views and actions.
 *
 * This is a part of lite Model/View/Controler design pattern.
 *
 */
class Controler
{
  /* protected */ public $gid;
  /* protected */ public $view;
  /* protected */ public $action;
  /* protected */ public $_viewParams   = array();
  /* protected */ public $_actionParams = array();

    public function request()
    {
    }

    public function viewsManagement()
    {
        $className = static::class . 'Views';
        if (! class_exists($className)) {
            throw new LogicException(sprintf('View class %s does not exist, nothing can be displayed', $className));
        }
        $wv = new $className($this, $this->gid, $this->view, $this->_viewParams);
        return $wv->display($this->view);
    }

    public function actionsManagement()
    {
        $className = static::class . 'Actions';
        if (! class_exists($className)) {
            throw new LogicException(sprintf('Action class %s does not exist, nothing can be processed', $className));
        }
        $wa = new $className($this, $this->gid);
        $wa->process($this->action, $this->_actionParams);
    }

    public function process()
    {
        $this->request();

        if ($this->action) {
            $this->actionsManagement();
        }

        return $this->viewsManagement();
    }
}
