<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\E2E\RelyingPartyOIDC;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\ConstraintViolation;

final class HasExpectedNonce implements Constraint
{
    private string $expected_nonce;

    public function __construct(string $expected_nonce)
    {
        $this->expected_nonce = $expected_nonce;
    }

    /**
     * @throws ConstraintViolation
     */
    #[\Override]
    public function assert(Token $token): void
    {
        if (! ($token instanceof UnencryptedToken)) {
            throw new ConstraintViolation('The token must be unencrypted to retrieve its claims');
        }
        $nonce_claim = $token->claims()->get('nonce', '');
        if (! hash_equals($this->expected_nonce, $nonce_claim)) {
            throw new ConstraintViolation(
                sprintf(
                    'Nonce claim (%s) does not have the expected value (%s)',
                    $nonce_claim,
                    $this->expected_nonce
                )
            );
        }
    }
}
