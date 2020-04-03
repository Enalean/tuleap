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

namespace Tuleap\PrometheusMetrics;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Server\Authentication\LoginCredentialSet;

/**
 * @psalm-immutable
 */
final class MetricsAuthCredential
{
    /**
     * @var string|null
     */
    private $username;
    /**
     * @var ConcealedString|null
     */
    private $password;

    private function __construct(?string $username, ?ConcealedString $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @psalm-pure
     */
    public static function noCredentialSet(): self
    {
        return new self(null, null);
    }

    /**
     * @psalm-pure
     */
    public static function fromLoginCredentialSet(LoginCredentialSet $credential_set): self
    {
        return new self($credential_set->getUsername(), $credential_set->getPassword());
    }

    public function doesCredentialMatch(string $known_username, ConcealedString $known_password): bool
    {
        if ($this->username === null || $this->password === null) {
            return false;
        }

        if ($this->username !== $known_username) {
            return false;
        }

        return $known_password->isIdenticalTo($this->password);
    }
}
