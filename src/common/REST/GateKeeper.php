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

/**
 * Ensure that request has the right properties before going RESTFul
 */
class GateKeeper
{
    public function assertAccess(PFUser $user)
    {
        if ($this->isTokenBasedAuthentication($user)) {
            return true;
        }
        if ($this->isCSRFSafe()) {
            return true;
        }
        throw new Exception('Referer doesn\'t match host. CSRF tentative ?');
    }

    private function isTokenBasedAuthentication(PFUser $user)
    {
        return $user->isAnonymous();
    }

    /**
     * @todo We should really check based on a csrf token but no way to get it done yet
     * @return bool
     */
    private function isCSRFSafe()
    {
        if ($this->isRequestFromSelf()) {
            return true;
        }
        return false;
    }

    private function isRequestFromSelf(): bool
    {
        $sec_fetch_site = $this->getSecFetchSite();
        if ($sec_fetch_site === '') {
            // We are still supporting browsers that does not send Fetch Metadata headers
            // so we need a fallback to a check of the Referer header
            return strtolower($this->getQueryHost()) === strtolower($this->getRefererHost());
        }

        return $sec_fetch_site === 'same-origin' && $this->getSecFetchMode() === 'cors';
    }

    private function getSecFetchSite(): string
    {
        return $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
    }

    private function getSecFetchMode(): string
    {
        return $_SERVER['HTTP_SEC_FETCH_MODE'] ?? '';
    }

    private function getQueryHost()
    {
        return $this->getUrlBase(\Tuleap\ServerHostname::HTTPSUrl());
    }

    private function getRefererHost()
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->getUrlBase($_SERVER['HTTP_REFERER']);
        }
        return '';
    }

    private function getUrlBase($url): string
    {
        $parsed_url = parse_url($url);
        $host       = $parsed_url['host'] ?? '';
        if ($host !== '') {
            $host = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        }
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';

        return "https://$host$port";
    }
}
