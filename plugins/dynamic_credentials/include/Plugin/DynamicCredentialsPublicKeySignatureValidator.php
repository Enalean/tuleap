<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\Plugin;

use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Config\ValueValidator;
use Tuleap\Cryptography\Asymmetric\SignaturePublicKey;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\InvalidKeyException;

final class DynamicCredentialsPublicKeySignatureValidator implements ValueValidator
{
    public static function buildSelf(): ValueValidator
    {
        return new self();
    }

    public function checkIsValid(string $value): void
    {
        $decoded_value = base64_decode($value, true);
        if ($decoded_value === false) {
            throw new InvalidConfigKeyValueException('Signature public key for dynamic credentials must be base64 encoded');
        }

        try {
            new SignaturePublicKey(new ConcealedString($decoded_value));
        } catch (InvalidKeyException $e) {
            throw new InvalidConfigKeyValueException(
                sprintf('Signature public key for dynamic credentials is not valid (%s)', $e->getMessage()),
                0,
                $e
            );
        }
    }
}
