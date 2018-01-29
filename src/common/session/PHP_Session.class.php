<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class PHP_Session
{
    public static function start()
    {
        session_cache_limiter('');
        self::configureSessionCookie();
        \Delight\Cookie\Session::start();
    }

    private static function configureSessionCookie()
    {
        $lifetime  = 0;
        $path      = '/';
        $domain    = null;
        $secure    = CookieManager::canCookieUseSecureFlag();
        $http_only = true;
        session_set_cookie_params($lifetime, $path, $domain, $secure, $http_only);
    }

    public static function destroy() {
        unset($_SESSION);
        session_destroy();
    } 

    public function clean() {
        $_SESSION = array();
    }

    protected function &getSession() {
        return $_SESSION;
    }

}
