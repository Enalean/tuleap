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

namespace Tuleap\Git\DefaultSettings;

use HTTPRequest;
use Tuleap\Git\RouterLink;

class DefaultSettingsRouter extends RouterLink
{
    /**
     * @var IndexController
     */
    private $index_controller;

    public function __construct(IndexController $index_controller)
    {
        $this->index_controller = $index_controller;
    }

    public function process(HTTPRequest $request)
    {
        switch ($request->get('action')) {
            case 'admin-default-settings':
                $this->index_controller->displayDefaultSettings($request);
                break;
            default:
                parent::process($request);
        }
    }
}
