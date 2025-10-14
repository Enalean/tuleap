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

namespace Tuleap\Test\Stubs\SeatManagement;

use Override;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Result;
use Tuleap\SeatManagement\CheckPublicKeyPresence;
use Tuleap\SeatManagement\Fault\NoPublicKeyFault;

final readonly class CheckPublicKeyPresenceStub implements CheckPublicKeyPresence
{
    private function __construct(
        private bool $has_key,
    ) {
    }

    public static function buildWithKey(): self
    {
        return new self(true);
    }

    public static function buildWithNoKey(): self
    {
        return new self(false);
    }

    #[Override]
    public function checkPresence(string $public_key_directory): Ok|Err
    {
        return $this->has_key ? Result::ok(null) : Result::err(NoPublicKeyFault::build());
    }
}
