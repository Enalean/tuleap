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
use Tuleap\Option\Option;
use Tuleap\SeatManagement\LicenseContent;
use Tuleap\SeatManagement\RetrieveLicenseContent;

final readonly class RetrieveLicenseContentStub implements RetrieveLicenseContent
{
    /**
     * @param Option<LicenseContent> $license_content
     */
    private function __construct(private Option $license_content)
    {
    }

    public static function buildWithLicenseContent(LicenseContent $license_content): self
    {
        return new self(Option::fromValue($license_content));
    }

    public static function buildWithoutLicenseContent(): self
    {
        return new self(Option::nothing(LicenseContent::class));
    }

    #[Override]
    public function retrieveLicenseContent(string $license_file_path): Option
    {
        return $this->license_content;
    }
}
