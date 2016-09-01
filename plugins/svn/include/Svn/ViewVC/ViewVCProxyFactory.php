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

namespace Tuleap\Svn\ViewVC;

use ProjectManager;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\ViewVCVersionChecker;

class ViewVCProxyFactory
{
    /**
     * @var ViewVCVersionChecker
     */
    private $viewvc_version_checker;

    public function __construct(ViewVCVersionChecker $viewvc_version_checker)
    {
        $this->viewvc_version_checker = $viewvc_version_checker;
    }

    /**
     * @return ViewVCProxy
     */
    public function getViewVCProxy(
        RepositoryManager $repository_manager,
        ProjectManager $project_manager,
        AccessHistorySaver $access_history_saver
    ) {
        if ($this->viewvc_version_checker->isTuleapViewVCInstalled()) {
            return new TuleapViewVCProxy($repository_manager, $project_manager, $access_history_saver);
        }

        return new EPELViewVCProxy($repository_manager, $project_manager, $access_history_saver);
    }
}
