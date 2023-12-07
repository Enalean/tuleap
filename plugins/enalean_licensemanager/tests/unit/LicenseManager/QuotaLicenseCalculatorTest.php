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

namespace Tuleap\Enalean\LicenseManager;

use Tuleap\Test\PHPUnit\TestCase;

final class QuotaLicenseCalculatorTest extends TestCase
{
    public function testQuotaIsNotReached(): void
    {
        self::assertFalse(QuotaLicenseCalculator::isQuotaExceeded(10, 100));
        self::assertFalse(QuotaLicenseCalculator::isQuotaExceedingSoon(10, 100));
    }

    public function testQuotaIsSoonReached(): void
    {
        self::assertFalse(QuotaLicenseCalculator::isQuotaExceeded(90, 100));
        self::assertTrue(QuotaLicenseCalculator::isQuotaExceedingSoon(90, 100));
    }

    public function testQuotaIsReached(): void
    {
        self::assertTrue(QuotaLicenseCalculator::isQuotaExceeded(110, 100));
        self::assertTrue(QuotaLicenseCalculator::isQuotaExceedingSoon(110, 100));
    }

    public function testQuotaWith0MaxUserIsConsideredToBeReached(): void
    {
        self::assertTrue(QuotaLicenseCalculator::isQuotaExceeded(100, 0));
        self::assertTrue(QuotaLicenseCalculator::isQuotaExceedingSoon(100, 0));
    }
}
