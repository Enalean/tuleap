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
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\SeatManagement\CheckLicenseContent;
use Tuleap\SeatManagement\Fault\InvalidLicenseContentFault;
use Tuleap\SeatManagement\LicenseContent;

final readonly class CheckLicenseContentStub implements CheckLicenseContent
{
    private function __construct(private bool $is_valid)
    {
    }

    public static function buildWithValid(): self
    {
        return new self(true);
    }

    public static function buildWithInvalid(): self
    {
        return new self(false);
    }

    #[Override]
    public function checkLicenseContent(LicenseContent $license_content): Ok|Err
    {
        return $this->is_valid
            ? Result::ok($license_content)
            : Result::err(InvalidLicenseContentFault::build());
    }
}
