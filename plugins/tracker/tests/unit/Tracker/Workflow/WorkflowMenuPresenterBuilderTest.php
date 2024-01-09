<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

use Tuleap\Event\Dispatchable;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class WorkflowMenuPresenterBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $builder = new WorkflowMenuPresenterBuilder(
            EventDispatcherStub::withCallback(static function (Dispatchable $event): Dispatchable {
                if ($event instanceof WorkflowMenuItemCollection) {
                    $event->addItem(new WorkflowMenuItem('/my/url', 'My label', 'my-data-test'));
                }

                return $event;
            })
        );

        $presenter = $builder->build(TrackerTestBuilder::aTracker()->build());

        self::assertCount(6, $presenter->items);
        self::assertSame('Transitions rules', $presenter->items[0]->label);
        self::assertSame('Global rules', $presenter->items[1]->label);
        self::assertSame('Field dependencies', $presenter->items[2]->label);
        self::assertSame('Triggers', $presenter->items[3]->label);
        self::assertSame('Webhooks', $presenter->items[4]->label);
        self::assertSame('My label', $presenter->items[5]->label);
    }
}
