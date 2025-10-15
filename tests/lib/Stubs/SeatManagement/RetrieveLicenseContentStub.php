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

use Lcobucci\JWT\UnencryptedToken;
use Override;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\SeatManagement\Fault\LicenseClaimsParsingFault;
use Tuleap\SeatManagement\LicenseContent;
use Tuleap\SeatManagement\RetrieveLicenseContent;

final readonly class RetrieveLicenseContentStub implements RetrieveLicenseContent
{
    /**
     * @param Ok<LicenseContent>|Err<Fault> $license_content
     */
    private function __construct(private Ok|Err $license_content)
    {
    }

    public static function buildWithLicenseContent(LicenseContent $license_content): self
    {
        return new self(Result::ok($license_content));
    }

    public static function buildWithoutLicenseContent(): self
    {
        return new self(Result::err(LicenseClaimsParsingFault::build('License claims parsing error')));
    }

    #[Override]
    public function retrieveLicenseContent(UnencryptedToken $token): Ok|Err
    {
        return $this->license_content;
    }
}
