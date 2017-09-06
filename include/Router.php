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

namespace Tuleap\MyTuleapContactSupport;

use HTTPRequest;

class Router
{
    /** @var ContactSupportController */
    private $contact_support_controller;

    public function __construct(ContactSupportController $contact_support_controller)
    {
        $this->contact_support_controller = $contact_support_controller;
    }

    public function route(HTTPRequest $request)
    {
        $action = $request->get('action');

        switch ($action) {
            case 'send-message':
                $this->contact_support_controller->contactSupport($request);
                break;
            case 'get-modal-content':
            default:
                $this->contact_support_controller->displayModalContent($request);
                break;
        }
    }
}
