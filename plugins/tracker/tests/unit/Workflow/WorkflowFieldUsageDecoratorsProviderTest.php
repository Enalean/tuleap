<?php
/**
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

namespace Tuleap\Tracker\Workflow;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Admin\LabelDecorator;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Workflow\ProvideGlobalRulesUsageByFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class WorkflowFieldUsageDecoratorsProviderTest extends TestCase
{
    public function testGetLabelDecoratorsReturnsEmptyArrayWhenFieldIsNotUsedInGlobalRules(): void
    {
        $field = $this->createMock(TrackerField::class);

        $provider = new WorkflowFieldUsageDecoratorsProvider(ProvideGlobalRulesUsageByFieldStub::withoutGlobalRules());

        self::assertSame([], $provider->getLabelDecorators($field));
    }

    public function testGetLabelDecoratorsReturnsGlobalRulesDecoratorWhenFieldIsUsedInGlobalRules(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();

        $field = $this->createMock(TrackerField::class);
        $field->method('getTracker')->willReturn($tracker);

        $provider = new WorkflowFieldUsageDecoratorsProvider(ProvideGlobalRulesUsageByFieldStub::withGlobalRules());

        $decorators = $provider->getLabelDecorators($field);

        self::assertCount(1, $decorators);
        self::assertInstanceOf(LabelDecorator::class, $decorators[0]);

        $expected_url = WorkflowUrlBuilder::buildGlobalRulesUrl($tracker);

        self::assertSame(dgettext('tuleap-tracker', 'Global rules'), $decorators[0]->label);
        self::assertSame(dgettext('tuleap-tracker', 'This field is used by global rules'), $decorators[0]->description);
        self::assertNull($decorators[0]->icon);
        self::assertSame($expected_url, $decorators[0]->url);
    }
}
