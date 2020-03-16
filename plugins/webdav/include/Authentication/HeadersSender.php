<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Webdav\Authentication;

class HeadersSender
{
    public function sendHeaders(): void
    {
        header('WWW-Authenticate: Basic realm="' . $GLOBALS['sys_name'] . ' WebDAV Authentication"');
        header('HTTP/1.0 401 Unauthorized');

        // text returned when user hit cancel
        echo $GLOBALS['Language']->getText('plugin_webdav_common', 'authentication_required');

        // The HTTP_BasicAuth (and digest) will return a 401 statuscode.
        // If there is no die() after that, the server will just do it's thing as usual
        // and override it with it's own statuscode (200, 404, 207, 201, or whatever was appropriate).
        // So the die() actually makes sure that the php script doesn't continue if the client
        // has an incorrect or no username and password.
        die();
    }
}
