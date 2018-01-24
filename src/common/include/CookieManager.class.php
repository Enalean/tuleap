<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

use Delight\Cookie\Cookie;

/**
 * CookieManager
 *
 * Manages cookies
 */
class CookieManager
{
    public function setCookie($name, $value, $expire = 0)
    {
        $cookie = $this->buildCookie($name);
        $cookie->setValue($value);
        $cookie->setExpiryTime($expire);

        return $cookie->save();
    }

    /**
     * @return Cookie
     */
    private function buildCookie($name)
    {
        $cookie = new Cookie($this->getInternalCookieName($name));
        $cookie->setHttpOnly(true);
        $cookie->setSecureOnly($this->canCookieBeSecure());
        $cookie->setDomain($this->getCookieDomain());
        $cookie->setSameSiteRestriction(Cookie::SAME_SITE_RESTRICTION_LAX);

        return $cookie;
    }

    public function configureSessionCookie()
    {
        $lifetime  = 0;
        $path      = '/';
        $domain    = $this->getCookieDomain();
        $secure    = $this->canCookieBeSecure();
        $http_only = true;
        session_set_cookie_params($lifetime, $path, $domain, $secure, $http_only);
    }

    /**
     * @return string|null
     */
    private function getCookieDomain()
    {
        $sys_cookie_domain = ForgeConfig::get('sys_cookie_domain');
        if (empty($sys_cookie_domain)) {
            return null;
        }

        $host = ForgeConfig::get('sys_default_domain');
        if ($this->canCookieBeSecure()) {
            $host = ForgeConfig::get('sys_https_host');
        }

        $sys_cookie_domain = $this->getHostNameWithoutPort($sys_cookie_domain);
        if ($this->getHostNameWithoutPort($host) === $sys_cookie_domain) {
            return null;
        }

        return $sys_cookie_domain;
    }

    /**
     * @return bool
     */
    private function canCookieBeSecure()
    {
        return (bool) ForgeConfig::get('sys_https_host');
    }

    private function getHostNameWithoutPort($domain) {
        if (strpos($domain, ':') !== false) {
            list($host,) = explode(':', $domain);
            return $host;
        }
        return $domain;
    }

    public function getCookie($name)
    {
        return Cookie::get($this->getInternalCookieName($name), '');
    }

    /**
     * @return bool
     */
    public function isCookie($name)
    {
        return Cookie::exists($this->getInternalCookieName($name));
    }

    public function removeCookie($name)
    {
        $cookie = $this->buildCookie($name);
        $cookie->delete();
    }

    /**
     * @return string
     */
    private function getInternalCookieName($name)
    {
        $cookie_prefix = ForgeConfig::get('sys_cookie_prefix');
        $cookie_name   = "${cookie_prefix}_${name}";

        if (! $this->canCookieBeSecure()) {
            return $cookie_name;
        }

        if ($this->getCookieDomain() === null) {
            return Cookie::PREFIX_HOST . $cookie_name;
        }

        return Cookie::PREFIX_SECURE . $cookie_name;
    }
}
