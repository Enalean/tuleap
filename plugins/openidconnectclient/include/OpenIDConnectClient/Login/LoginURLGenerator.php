<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\Login;

use Tuleap\OpenIDConnectClient\Provider\Provider;

/**
 * @psalm-immutable
 */
class LoginURLGenerator
{
    /**
     * @var string
     */
    private $base_url;

    public function __construct(string $base_url)
    {
        $this->base_url = $base_url;
    }

    public function getLoginURL(Provider $provider, ?string $return_to): string
    {
        $login_url = $this->base_url . '/login_to/' . urlencode((string) $provider->getId());

        if ($return_to) {
            $login_url .= '?return_to=' . urlencode($return_to);
        }

        return $login_url;
    }
}
