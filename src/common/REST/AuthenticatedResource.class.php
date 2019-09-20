<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Luracast\Restler\iUseAuthentication;
use Luracast\Restler\RestException;
use ForgeConfig;

class AuthenticatedResource implements iUseAuthentication
{

    public const ACCESS_LEVEL_NORMAL      = 0;
    public const ACCESS_LEVEL_FORBID_ANON = 1;

    public const REALM = 'Restricted Tuleap API';

    protected $is_authenticated;

    public function __setAuthenticationStatus($is_authenticated = false) // phpcs:ignore
    {
        $this->is_authenticated = $is_authenticated;
    }

    private function getAccessLevel()
    {
        if (! ForgeConfig::areAnonymousAllowed()) {
            return self::ACCESS_LEVEL_FORBID_ANON;
        }
        return self::ACCESS_LEVEL_NORMAL;
    }

    /**
     * Not an API resource but an inheritable method.
     * We use the @access tag to exclude it from the explorer
     *
     * @access private
     */
    protected function checkAccess()
    {
        if (! $this->is_authenticated && $this->getAccessLevel() === self::ACCESS_LEVEL_FORBID_ANON) {
            throw new RestException(401);
        }
    }
}
