<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

use \PFUser;
use \Exception;
use \ForgeConfig;

/**
 * Ensure that request has the right properties before going RESTFul
 */
class GateKeeper {

    public function assertAccess(PFUser $user) {
        if ($this->isTokenBasedAuthentication($user)) {
            if ($this->isHTTPS() || $this->canReachApiWithoutHTTPS()) {
                return true;
            }
            throw new Exception('The API is only accessible over HTTPS');
        } else {
            if ($this->isCSRFSafe()) {
                return true;
            }
            throw new Exception('Referer doesn\'t match host. CSRF tentative ?');
        }
    }

    private function isTokenBasedAuthentication(PFUser $user) {
        return $user->isAnonymous();
    }

    private function isHTTPS() {
        return isset($_SERVER['HTTPS']);
    }

    private function canReachApiWithoutHTTPS() {
        return $this->isInDebugMode() || ForgeConfig::get('sys_rest_api_over_http');
    }

    private function isInDebugMode() {
        return isset($GLOBALS['DEBUG_MODE']) && $GLOBALS['DEBUG_MODE'] == 1;
    }

    /**
     * @todo We should really check based on a csrf token but no way to get it done yet
     * @return boolean
     */
    private function isCSRFSafe() {
        if ($this->isRequestFromSelf()) {
            return true;
        }
        return false;
    }

    private function isRequestFromSelf() {
        return $this->getQueryHost() === $this->getRefererHost();
    }

    private function getQueryHost() {
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            return $this->getUrlBase($scheme.$_SERVER['HTTP_HOST']);
        }
        return $this->getUrlBase(get_server_url());
    }

    private function getRefererHost() {
        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->getUrlBase($_SERVER['HTTP_REFERER']);
        }
    }

    private function getUrlBase($url) {
        $parsed_url = parse_url($url);
        $scheme = '';
        if (! ForgeConfig::get('sys_rest_api_over_http')) {
            $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        }
        $host   = isset($parsed_url['host'])   ? $parsed_url['host'] : '';
        $port   = isset($parsed_url['port'])   ? ':' . $parsed_url['port'] : '';

        return "$scheme$host$port";
    }

}
