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

namespace Tuleap\Tracker\Action;

use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

final class CollectMovableExternalFieldEventTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testTheFieldIsNotMigrateableWhenNoPluginHasAnswered(): void
    {
        $event = new CollectMovableExternalFieldEvent(
            StringFieldBuilder::aStringField(1)->build(),
            StringFieldBuilder::aStringField(2)->build(),
        );

        self::assertFalse($event->isFieldMigrateable());
        self::assertFalse($event->isFieldFullyMigrateable());
    }

    public function testTheFieldIsFullyMigrateableWhenItHasBeenMarkAsIs(): void
    {
        $event = new CollectMovableExternalFieldEvent(
            StringFieldBuilder::aStringField(1)->build(),
            StringFieldBuilder::aStringField(2)->build(),
        );

        $event->markFieldAsFullyMigrateable();

        self::assertTrue($event->isFieldMigrateable());
        self::assertTrue($event->isFieldFullyMigrateable());
    }

    public function testTheFieldIsNotMigrateableWhenItHasBeenMarkAsIs(): void
    {
        $event = new CollectMovableExternalFieldEvent(
            StringFieldBuilder::aStringField(1)->build(),
            StringFieldBuilder::aStringField(2)->build(),
        );

        $event->markFieldAsNotMigrateable();

        self::assertFalse($event->isFieldMigrateable());
        self::assertFalse($event->isFieldFullyMigrateable());
    }
}
