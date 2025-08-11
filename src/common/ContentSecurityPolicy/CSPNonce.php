<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\ContentSecurityPolicy;

final class CSPNonce
{
    public readonly string $value;
    private static ?self $instance = null;

    private function __construct()
    {
        $this->value = sodium_bin2base64(random_bytes(32), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }

    public static function build(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __serialize(): never
    {
        $this->rejectSerialization();
    }

    public function __unserialize(array $data): never
    {
        $this->rejectSerialization();
    }

    private function rejectSerialization(): never
    {
        throw new \LogicException('Do not attempt to serialize the CSP nonce, it must not be re-used');
    }
}
