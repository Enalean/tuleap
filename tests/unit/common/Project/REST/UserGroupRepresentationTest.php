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

namespace Tuleap\Project\REST;

use Exception;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserGroupRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestWith(['102_3'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['102_11'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['101'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['2'])]
    public function testValidRESTUserGroupIdentifierAreNotRejected(string $identifier): void
    {
        $this->expectNotToPerformAssertions();
        UserGroupRepresentation::checkRESTIdIsAppropriate($identifier);
    }

    #[\PHPUnit\Framework\Attributes\TestWith([''])]
    #[\PHPUnit\Framework\Attributes\TestWith(['45'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['102_2'])]
    public function testNotValidRESTUserGroupIdentifierAreRejected(string $invalid_identifier): void
    {
        $this->expectException(Exception::class);
        UserGroupRepresentation::checkRESTIdIsAppropriate($invalid_identifier);
    }
}
