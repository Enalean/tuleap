<?php
/**
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Codendi. If not, see <http://www.gnu.org/licenses/
  */
/**
 *
 * This is a part of lite Model/View/Controler design pattern.
 *
 */

class PluginViews
{

    protected $request;

    /**
     *
     * @var PluginController
     */
    protected $controller;

    public function __construct($controller)
    {
        $this->controller   = $controller;
        $this->request      = $controller->getRequest();
        $this->HTMLPurifier = Codendi_HTMLPurifier::instance();
        $this->user         = $controller->getUser();
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getData()
    {
        return $this->controller->getData();
    }

    public function display($name, $params = array())
    {
        if (empty($name)) {
            return false;
        }
        call_user_func_array(array($this,$name), $params);
    }

    public static function linkTo($link, $href, $options = '')
    {
        $linkTo = '<a href="' . $href . '" ' . $options . ' >' . $link . '</a>';
        return $linkTo;
    }
}
