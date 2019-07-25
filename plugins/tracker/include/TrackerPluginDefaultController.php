<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker;

use HTTPRequest;
use TrackerManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class TrackerPluginDefaultController implements DispatchableWithRequest
{
    /**
     * @var TrackerManager
     */
    private $tracker_manager;

    public function __construct(TrackerManager $tracker_manager)
    {
        $this->tracker_manager = $tracker_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        require_once __DIR__ . '/../../../src/www/project/admin/permissions.php';
        // Inherited from old .htaccess (needed for reports, linked artifact view, etc)
        ini_set('max_execution_time', 1800);
        $this->tracker_manager->process($request, $request->getCurrentUser());
    }
}
