<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use PFUser;
use Exception;
use ForgeConfig;
use HTTPRequest;

/**
 * Ensure that request has the right properties before going RESTFul
 */
class GateKeeper
{

    public function assertAccess(PFUser $user, HTTPRequest $request)
    {
        if ($this->isTokenBasedAuthentication($user)) {
            if ($request->isSecure() || $this->canReachApiWithoutHTTPS()) {
                return true;
            }
            throw new Exception('The API is only accessible over HTTPS');
        } else {
            if ($this->isCSRFSafe($request)) {
                return true;
            }
            throw new Exception('Referer doesn\'t match host. CSRF tentative ?');
        }
    }

    private function isTokenBasedAuthentication(PFUser $user)
    {
        return $user->isAnonymous();
    }

    private function canReachApiWithoutHTTPS()
    {
        return ForgeConfig::get('sys_rest_api_over_http');
    }

    /**
     * @todo We should really check based on a csrf token but no way to get it done yet
     * @return bool
     */
    private function isCSRFSafe(HTTPRequest $request)
    {
        if ($this->isRequestFromSelf($request)) {
            return true;
        }
        return false;
    }

    private function isRequestFromSelf(HTTPRequest $request)
    {
        return strtolower($this->getQueryHost($request)) === strtolower($this->getRefererHost());
    }

    private function getQueryHost(HTTPRequest $request)
    {
        return $this->getUrlBase($request->getServerUrl());
    }

    private function getRefererHost()
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->getUrlBase($_SERVER['HTTP_REFERER']);
        }
    }

    private function getUrlBase($url)
    {
        $parsed_url = parse_url($url);
        $scheme = '';
        if (! ForgeConfig::get('sys_rest_api_over_http')) {
            $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        }
        $host = isset($parsed_url['host']) ? idn_to_ascii($parsed_url['host'], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';

        return "$scheme$host$port";
    }
}
