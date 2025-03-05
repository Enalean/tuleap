<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeaturesToReorderProxyTest extends TestCase
{
    public function testItBuildFeatureToReorder(): void
    {
        $element_to_order = new FeatureElementToOrderInvolvedInChangeRepresentation([964], 'before', 900);

        $feature_to_order = FeaturesToReorderProxy::buildFromRESTRepresentation($element_to_order);
        self::assertSame($element_to_order->ids, $feature_to_order?->getIds());
        self::assertSame($element_to_order->direction, $feature_to_order?->getDirection());
        self::assertSame($element_to_order->compared_to, $feature_to_order?->getComparedTo());
    }

    public function testItReturnsTrueWhenOrderIsBefore(): void
    {
        $element_to_order = new FeatureElementToOrderInvolvedInChangeRepresentation([964], 'before', 900);

        $feature_to_order = FeaturesToReorderProxy::buildFromRESTRepresentation($element_to_order);
        self::assertTrue($feature_to_order?->isBefore());
    }

    public function testItReturnsFalseWhenOrderIsAfter(): void
    {
        $element_to_order = new FeatureElementToOrderInvolvedInChangeRepresentation([964], 'after', 900);

        $feature_to_order = FeaturesToReorderProxy::buildFromRESTRepresentation($element_to_order);
        self::assertFalse($feature_to_order?->isBefore());
    }
}
