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

namespace Tuleap\AgileDashboard\Move;

use Tuleap\AgileDashboard\Test\Builders\BurnupTestBuilder;
use Tuleap\Tracker\Action\CollectMovableExternalFieldEvent;

class AgileDashboardMovableFieldsCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItMarksTheFieldAsFullyMigrateableWhenBothSourceAndDestinationAreBurnupFields(): void
    {
        $event = new CollectMovableExternalFieldEvent(
            BurnupTestBuilder::aBurnupField(),
            BurnupTestBuilder::aBurnupField(),
        );

        AgileDashboardMovableFieldsCollector::collectMovableFields($event);

        self::assertTrue($event->isFieldMigrateable());
        self::assertTrue($event->isFieldFullyMigrateable());
    }
}
