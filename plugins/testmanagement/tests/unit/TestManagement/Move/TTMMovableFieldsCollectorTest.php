<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Move;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\TestManagement\Test\Builders\StepsDefinitionFieldBuilder;
use Tuleap\TestManagement\Test\Builders\StepExecutionFieldBuilder;
use Tuleap\Tracker\Action\CollectMovableExternalFieldEvent;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TTMMovableFieldsCollectorTest extends TestCase
{
    public function testItMarksFieldAsFullyMigrateableWhenItIsAStepDefinitionField(): void
    {
        $event = new CollectMovableExternalFieldEvent(
            StepsDefinitionFieldBuilder::aStepsDefinitionField(1)->build(),
            StepsDefinitionFieldBuilder::aStepsDefinitionField(2)->build(),
        );

        TTMMovableFieldsCollector::collectMovableFields($event);

        self::assertTrue($event->isFieldMigrateable());
        self::assertTrue($event->isFieldFullyMigrateable());
    }

    public function testItMarksFieldAsNotMigrateableWhenItIsAStepExecutionField(): void
    {
        $event = new CollectMovableExternalFieldEvent(
            StepExecutionFieldBuilder::aStepExecutionField(),
            StepExecutionFieldBuilder::aStepExecutionField(),
        );

        TTMMovableFieldsCollector::collectMovableFields($event);

        self::assertFalse($event->isFieldMigrateable());
    }
}
