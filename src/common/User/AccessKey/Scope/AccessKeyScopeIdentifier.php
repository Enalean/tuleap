<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey\Scope;

/**
 * @psalm-immutable
 */
final class AccessKeyScopeIdentifier
{
    /**
     * @var string
     */
    private $identifier;

    private function __construct(string $identifier_key)
    {
        $this->identifier = $identifier_key;
    }

    /**
     * @psalm-pure
     */
    public static function fromIdentifierKey(string $identifier_key): self
    {
        if (preg_match('/^[^\s:]+:[^\s:]+$/', $identifier_key) !== 1) {
            throw new InvalidScopeIdentifierKeyException($identifier_key);
        }

        return new self($identifier_key);
    }

    public function toString(): string
    {
        return $this->identifier;
    }
}
