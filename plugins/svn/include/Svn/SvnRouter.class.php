<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

namespace Tuleap\Svn;

use HTTPRequest;
use \Tuleap\Svn\Explorer\ExplorerController;
use \Tuleap\Svn\Repository\RepositoryManager;
use \Tuleap\Svn\Dao;
use Valid_WhiteList;

class SvnRouter {

    const DEFAULT_ACTION = 'index';

    public function __construct() {
    }

    /**
     * Routes the request to the correct controller
     * @param HTTPRequest $request
     * @return void
     */
    public function route(HTTPRequest $request) {
        if ( ! $request->get('action')) {
            $this->useDefaultRoute($request);
            return;
        }

        $action = $request->get('action');
        switch ($action) {
            case "createRepo":
                $controller = new ExplorerController(new RepositoryManager(new Dao()));
                $controller->$action($this->getService($request), $request);
                break;
            default:
                $this->useDefaultRoute($request);
                break;
        }
    }

    /**
     * @param HTTPRequest $request
     */
    private function useDefaultRoute(HTTPRequest $request) {
        $action = self::DEFAULT_ACTION;
        $controller = new ExplorerController(new RepositoryManager(new Dao()));
        $controller->$action( $this->getService($request), $request );
    }

    /**
     * Retrieves the SVN Service instance matching the request group id.
     *
     * @param HTTPRequest $request
     *
     * @return ServiceSvn
     */
    private function getService(HTTPRequest $request) {
        return $request->getProject()->getService('plugin_svn');
    }
}
