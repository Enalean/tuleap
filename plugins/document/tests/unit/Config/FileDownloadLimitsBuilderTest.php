<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Config;

use Tuleap\ForgeConfigSandbox;

final class FileDownloadLimitsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testItProvidesDefaultWhenNotSet(): void
    {
        $limits = (new FileDownloadLimitsBuilder())->build();

        self::assertEquals(50, $limits->getWarningThreshold());
        self::assertEquals(2000, $limits->getMaxArchiveSize());
    }

    public function testItProvidesDefaultWhenInvalidValue(): void
    {
        \ForgeConfig::set(FileDownloadLimits::WARNING_THRESHOLD_NAME, -12);
        \ForgeConfig::set(FileDownloadLimits::MAX_ARCHIVE_SIZE_NAME, -200);

        $limits = (new FileDownloadLimitsBuilder())->build();

        self::assertEquals(50, $limits->getWarningThreshold());
        self::assertEquals(2000, $limits->getMaxArchiveSize());
    }

    public function testItProvidesLimits(): void
    {
        \ForgeConfig::set(FileDownloadLimits::WARNING_THRESHOLD_NAME, 12);
        \ForgeConfig::set(FileDownloadLimits::MAX_ARCHIVE_SIZE_NAME, 200);

        $limits = (new FileDownloadLimitsBuilder())->build();

        self::assertEquals(12, $limits->getWarningThreshold());
        self::assertEquals(200, $limits->getMaxArchiveSize());
    }
}
