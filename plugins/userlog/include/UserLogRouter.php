<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Userlog;

use HTTPRequest;
use Rule_Date;
use Rule_GreaterOrEqual;
use Rule_Int;
use UserLogManager;
use Valid;

class UserLogRouter
{
    /**
     * @var UserLogExporter
     */
    private $user_log_exporter;

    /**
     * @var UserLogManager
     */
    private $user_log_manager;

    public function __construct(UserLogExporter $user_log_exporter, UserLogManager $user_log_manager)
    {
        $this->user_log_exporter = $user_log_exporter;
        $this->user_log_manager  = $user_log_manager;
    }

    /**
     * Routes the request to the correct controller
     * @return void
     */
    public function route(HTTPRequest $request)
    {
        $this->checkAccess($request);

        $offset = $this->validAndExtractOffset($request);
        $day    = $this->validAndExtractDate($request);

        if (! $request->get('action')) {
            $this->useDefaultRoute($offset, $day);

            return;
        }

        $action = $request->get('action');

        switch ($action) {
            case "export":
                $this->user_log_exporter->exportLogs($day);
                break;
            default:
                $this->useDefaultRoute($offset, $day);
                break;
        }
    }

    private function checkAccess(HTTPRequest $request)
    {
        $request->checkUserIsSuperUser();
    }

    private function validAndExtractOffset(HTTPRequest $request)
    {
        $valid = new Valid('offset');
        $valid->setErrorMessage('Invalid offset submitted. Force it to 0 (zero).');
        $valid->addRule(new Rule_Int());
        $valid->addRule(new Rule_GreaterOrEqual(0));
        if ($request->valid($valid)) {
            $offset = $request->get('offset');
        } else {
            $offset = 0;
        }

        return $offset;
    }

    private function validAndExtractDate(HTTPRequest $request)
    {
        $valid = new Valid('day');
        $valid->addRule(new Rule_Date(), 'Invalid date submitted. Force it to today.');
        if ($request->valid($valid)) {
            $day = $request->get('day');
        }

        if (! $day) {
            $day = date('Y-n-j');
        }

        return $day;
    }

    private function useDefaultRoute($offset, $day)
    {
        $this->user_log_manager->displayLogs($offset, $day);
    }
}
