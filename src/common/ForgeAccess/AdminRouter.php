<?php
/**
  * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class ForgeAccess_AdminRouter
{

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var ForgeAccess_AdminController
     */
    private $controller;

    public function __construct(
        ForgeAccess_AdminController $controller,
        Codendi_Request $request
    ) {
        $this->controller = $controller;
        $this->request    = $request;
    }

    public function route()
    {
        if (! $this->request->getCurrentUser()->isSuperUser()) {
            $this->controller->notSiteAdmin($this->request);
        } elseif ($this->request->get('update')) {
            $this->controller->update();
        } elseif ($this->request->get('update-anonymous-access')) {
            $this->controller->updateAnonymousAccess();
        } else {
            $this->controller->index();
        }
    }
}
