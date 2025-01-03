<?php
/**
  * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

class PluginActions
{
    protected $controller;

    /** @var Codendi_Request*/
    protected $request;
    /**
     * @var PFUser
     */
    protected $user;

    public function __construct($controller)
    {
        $this->controller = $controller;
        $this->user       = $controller->getUser();
        $this->request    = $controller->getRequest();
    }

    public function getController()
    {
        return $this->controller;
    }

    public function setController(PluginController $controller)
    {
        $this->controller = $controller;
    }

    public function getData()
    {
        return $this->controller->getData();
    }

    public function addData($data)
    {
        $this->controller->addData($data);
    }

    public function process($action_name, $params)
    {
        if (str_starts_with($action_name, '__')) {
            throw new Exception('Unexpected action_name value');
        }
        /** @psalm-taint-escape callable */
        $callback = [$this, $action_name];
        return call_user_func_array($callback, $params);
    }
}
