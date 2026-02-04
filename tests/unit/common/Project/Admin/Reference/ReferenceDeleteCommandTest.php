<?php
/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Reference;

use PHPUnit\Framework\TestCase;
use ReferenceManager;
use Tuleap\Test\Builders\ReferenceBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReferenceDeleteCommandTest extends TestCase
{
    public function testItDeletesSystemReferenceSuccessfully(): void
    {
        $reference_manager        = $this->createMock(ReferenceManager::class);
        $reference_delete_command = new ReferenceDeleteCommand($reference_manager);

        $reference = ReferenceBuilder::anArtReference()->isASystemReference()->build();

        $reference_manager->expects($this->once())
            ->method('deleteSystemReference')
            ->with($reference)
            ->willReturn(true);

        $result = $reference_delete_command->deleteReference($reference);
        self::assertTrue($result);
    }

    public function testItFailsToDeleteSystemReference(): void
    {
        $reference_manager        = $this->createMock(ReferenceManager::class);
        $reference_delete_command = new ReferenceDeleteCommand($reference_manager);

        $reference = ReferenceBuilder::anArtReference()->isASystemReference()->build();

        $reference_manager->expects($this->once())
            ->method('deleteSystemReference')
            ->with($reference)
            ->willReturn(false);

        $result = $reference_delete_command->deleteReference($reference);
        self::assertFalse($result);
    }

    public function testItDeletesProjectReferenceSuccessfully(): void
    {
        $reference_manager        = $this->createMock(ReferenceManager::class);
        $reference_delete_command = new ReferenceDeleteCommand($reference_manager);

        $reference = ReferenceBuilder::anArtReference()->isAProjectReference()->build();

        $reference_manager->expects($this->once())
            ->method('deleteReference')
            ->with($reference)
            ->willReturn(true);

        $result = $reference_delete_command->deleteReference($reference);
        self::assertTrue($result);
    }

    public function testItFailsToDeleteProjectReference(): void
    {
        $reference_manager        = $this->createMock(ReferenceManager::class);
        $reference_delete_command = new ReferenceDeleteCommand($reference_manager);

        $reference = ReferenceBuilder::anArtReference()->isAProjectReference()->build();

        $reference_manager->expects($this->once())
            ->method('deleteReference')
            ->with($reference)
            ->willReturn(false);

        $result = $reference_delete_command->deleteReference($reference);
        self::assertFalse($result);
    }
}
