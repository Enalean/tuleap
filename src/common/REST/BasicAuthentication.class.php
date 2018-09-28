<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\REST;

use \Luracast\Restler\iAuthenticate;
use \Luracast\Restler\InvalidAuthCredentials;

class BasicAuthentication implements iAuthenticate
{
    public function __isAllowed() {
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $current_user = \UserManager::instance()->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

            if ($current_user->isLoggedIn()) {
                return true;
            }

            throw new InvalidAuthCredentials(401, 'Basic Authentication Required');
        }
    }

    public static function __getMaximumSupportedVersion() {
        return 2;
    }

    /**
     * Needed due to iAuthenticate interface since Restler v3.0.0-RC6
     */
    public function __getWWWAuthenticateString()
    {
        return 'Basic realm="'.AuthenticatedResource::REALM.'" Token realm="'.AuthenticatedResource::REALM.'" AccessKey realm="'.AuthenticatedResource::REALM.'"';
    }
}
