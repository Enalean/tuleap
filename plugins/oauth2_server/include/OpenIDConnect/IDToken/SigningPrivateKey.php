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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\OpenIDConnect\IDToken;

use Lcobucci\JWT\Signer\Key;
use Tuleap\Cryptography\ConcealedString;

final class SigningPrivateKey
{
    /**
     * @var SigningPublicKey
     * @psalm-readonly
     */
    private $public_key;
    /**
     * @var ConcealedString
     * @psalm-readonly
     */
    private $private_key_material;

    public function __construct(SigningPublicKey $public_key, ConcealedString $private_key_material)
    {
        $this->public_key           = $public_key;
        $this->private_key_material = $private_key_material;
    }

    public function getFingerprintPublicKey(): string
    {
        return $this->public_key->getFingerprint();
    }

    public function getPrivateKey(): Key
    {
        return new Key($this->private_key_material->getString());
    }
}
