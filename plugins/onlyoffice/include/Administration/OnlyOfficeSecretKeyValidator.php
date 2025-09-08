<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Administration;

use Tuleap\Config\SecretValidator;
use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Cryptography\ConcealedString;

/**
 * @psalm-immutable
 */
final class OnlyOfficeSecretKeyValidator implements SecretValidator
{
    private function __construct()
    {
    }

    #[\Override]
    public static function buildSelf(): self
    {
        return new self();
    }

    /**
     * @throws InvalidConfigKeyValueException
     */
    #[\Override]
    public function checkIsValid(ConcealedString $value): void
    {
        if ($value->isIdenticalTo(new ConcealedString(''))) {
            throw new InvalidConfigKeyValueException('Secret cannot be empty');
        }

        if (strlen($value->getString()) < 32) {
            throw new InvalidConfigKeyValueException('Secret length cannot be less than 32 characters');
        }
    }
}
