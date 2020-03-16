<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Git\RemoteServer\Gerrit;

use Git_RemoteServer_GerritServer;
use Rule_UserName;

class HttpUserValidator
{
    public function isLoginAnHTTPUserLogin($login)
    {
        $pattern = '/^' . Rule_UserName::RESERVED_PREFIX . Git_RemoteServer_GerritServer::GENERIC_USER_PREFIX . '[0-9]+$/';

        return preg_match($pattern, $login);
    }
}
