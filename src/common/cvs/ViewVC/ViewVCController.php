<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\CVS\ViewVC;

use HTTPRequest;
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ViewVCController implements DispatchableWithRequest
{

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! user_isloggedin()) {
            throw new ForbiddenException();
        }
        // be backwards compatible with old viewvc.cgi links that are now redirected
        $root    = $request->get('root');
        if (!$root) {
            $root = $request->get('cvsroot');
        }

        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProjectByUnixName($root);
        if (! $project) {
            throw new NotFoundException();
        }

        $viewvc_proxy = new ViewVCProxy();
        $viewvc_proxy->displayContent($project, $request, $this->fixPathInfo($variables));
    }

    private function fixPathInfo(array $variables) : string
    {
        if (isset($variables['path']) && $variables['path'] !== '') {
            return $this->addTrailingSlash($this->addLeadingSlash($variables['path']));
        }
        return '/';
    }

    private function addLeadingSlash(string $path) : string
    {
        if ($path[0] !== '/') {
            return '/' . $path;
        }
        return $path;
    }

    private function addTrailingSlash(string $path) : string
    {
        if (strrpos($path, "/") !== (strlen($path) - 1)) {
            return $path . '/';
        }
        return $path;
    }
}
