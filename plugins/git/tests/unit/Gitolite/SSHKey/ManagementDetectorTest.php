<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite\SSHKey;

use Tuleap\Git\GlobalParameterDao;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ManagementDetectorTest extends TestCase
{
    public function testItIsAbleToFindThatTuleapManagesAuthorizedKeysFile(): void
    {
        $global_parameter_dao = $this->createMock(GlobalParameterDao::class);
        $global_parameter_dao->method('isAuthorizedKeysFileManagedByTuleap')->willReturn(false);

        $management_detector = new ManagementDetector($global_parameter_dao);

        self::assertFalse($management_detector->isAuthorizedKeysFileManagedByTuleap());
    }

    public function testItIsAbleToDetectThatTuleapManagesAuthorizedKeysFile(): void
    {
        $global_parameter_dao = $this->createMock(GlobalParameterDao::class);
        $global_parameter_dao->method('isAuthorizedKeysFileManagedByTuleap')->willReturn(true);

        $management_detector = new ManagementDetector($global_parameter_dao);

        self::assertTrue($management_detector->isAuthorizedKeysFileManagedByTuleap());
    }
}
