<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace Tuleap\REST;

use Luracast\Restler\iAuthenticate;
use Luracast\Restler\Restler;

class TuleapRESTAuthentication implements iAuthenticate
{
    /**
     * @var Restler|null
     * @psalm-readonly
     */
    public $restler;

    public function __isAllowed() // phpcs:ignore
    {
        $rest_authentication_flow = new RESTAuthenticationFlowIsAllowed(UserManager::build(), RESTLogger::getLogger());
        return $rest_authentication_flow->isAllowed($this->restler->apiMethodInfo ?? null);
    }

    public static function __getMaximumSupportedVersion() // phpcs:ignore
    {
        return 2;
    }

    /**
     * Needed due to iAuthenticate interface since Restler v3.0.0-RC6
     */
    public function __getWWWAuthenticateString() // phpcs:ignore
    {
        return 'Basic realm="' . AuthenticatedResource::REALM . ' ' .
            'Token realm="' . AuthenticatedResource::REALM . '" ' .
            'AccessKey realm="' . AuthenticatedResource::REALM . '" ' .
            'Bearer realm="' . AuthenticatedResource::REALM . '"';
    }
}
