<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\Authentication;

use RandomNumberGenerator;
use Tuleap\Cryptography\ConcealedString;

class StateFactory
{
    /**
     * @var string
     */
    private static $key;

    /**
     * @var string
     */
    private static $nonce;

    /**
     * @var ConcealedString
     */
    private static $pkce_code_verifier;

    public function __construct(RandomNumberGenerator $random_number_generator)
    {
        if (self::$key === null) {
            self::$key = $random_number_generator->getNumber();
        }
        if (self::$nonce === null) {
            self::$nonce = $random_number_generator->getNumber();
        }
        if (self::$pkce_code_verifier === null) {
            self::$pkce_code_verifier = new ConcealedString(sodium_bin2hex(random_bytes(32)));
        }
    }

    public function createState(int $provider_id, ?string $return_to = null): State
    {
        return new State($provider_id, $return_to, self::$key, self::$nonce, self::$pkce_code_verifier);
    }
}
