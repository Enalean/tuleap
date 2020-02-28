<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Cryptography\Asymmetric;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\InvalidKeyException;
use Tuleap\Cryptography\Key;

class SignaturePublicKey extends Key
{
    public function __construct(ConcealedString $key_data)
    {
        $raw_key_data        = $key_data->getString();
        $raw_key_data_length = \mb_strlen($raw_key_data, '8bit');
        \sodium_memzero($raw_key_data);
        if ($raw_key_data_length !== \SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new InvalidKeyException('Signature public key must be SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES long');
        }
        parent::__construct($key_data);
    }
}
