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
 *
 */

namespace Tuleap\Request;

/**
 * This interface distinguish routes that are managed without Authorization or Authentication prior to routing.
 *
 * From the roots of Tuleap, there always been some level of authorization that was handled transparently for developer
 * - Access to platform (with or without login)
 * - Management of project access (restricted users & co)
 *
 * However this lead to cumbersome design with a central URL verification point (URLVerification objects) that sends
 * events to know in which project a resource is supposed to belong & so on.
 *
 * This interface is supposed to be implemented by Routes that need to manage permissions entirely on their own.
 * As a developer or review be very careful, you are alone here, no Black^WBrown magic is there to protect yourself.
 *
 * @package Tuleap\Request
 */
interface DispatchableWithRequestNoAuthz extends DispatchableWithRequest
{
    /**
     * @param \URLVerification $url_verification
     * @param \HTTPRequest $request
     * @param array $variables
     *
     * @return boolean Whether access is granted or not
     */
    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables);
}
