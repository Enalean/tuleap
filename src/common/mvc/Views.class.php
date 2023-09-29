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
 * Representation of model.
 *
 * This is a part of lite Model/View/Controler design pattern.
 *
 */
abstract class Views
{
  /* protected array */  public $html_params;
  /* protected string */ public $view;
  /* protected Controler */ public $_controler;

    public function View(&$controler, $view = null, $params = [])
    {
        $this->_controler =& $controler;
        $this->view       = $view;
    }

    public function getControler()
    {
        return $this->_controler;
    }

    abstract public function header();

    public function footer()
    {
        site_project_footer($this->html_params);
    }

    public function main()
    {
    }

    public function display($view = '')
    {
        $this->header();
        if (! empty($view)) {
            $this->$view();
        }
        $this->footer();
    }
}
