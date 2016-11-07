<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\SvnCore\Admin;

use HTTPRequest;
use PFUser;

class Router
{
    /**
     * @var TokenController
     */
    private $token_controller;
    /**
     * @var CacheController
     */
    private $cache_controller;

    public function __construct(CacheController $cache_controller, TokenController $token_controller)
    {
        $this->token_controller = $token_controller;
        $this->cache_controller = $cache_controller;
    }

    public function process(HTTPRequest $request)
    {
        $this->checkAccess($request->getCurrentUser());

        $controller = $this->getController($request);
        $controller->process($request);
    }

    private function checkAccess(PFUser $user)
    {
        if (! $user->isSuperUser()) {
            $GLOBALS['Response']->redirect('/');
        }
    }

    /**
     * @return Controller
     */
    private function getController(HTTPRequest $request)
    {
        $requested_pane = $request->get('pane');

        switch ($requested_pane) {
            case 'cache':
                return $this->cache_controller;
            case 'token':
                return $this->token_controller;
        }

        $GLOBALS['Response']->redirect('/admin/svn/index.php?pane=cache');
    }
}
