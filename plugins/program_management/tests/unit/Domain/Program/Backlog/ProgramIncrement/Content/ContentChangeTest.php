<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;

final class ContentChangeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsWhenBothFeatureToAddAndReorderAreNull(): void
    {
        $this->expectException(AddOrOrderMustBeSetException::class);
        ContentChange::fromRESTRepresentation(null, null);
    }

    public function testItBuildsAValidPayloadWhenReorderIsNull(): void
    {
        $change = ContentChange::fromRESTRepresentation(123, null);
        self::assertSame(123, $change->potential_feature_id_to_add);
        self::assertNull($change->elements_to_order);
    }

    public function testItBuildsAValidPayloadWhenFeatureToAddIsNull(): void
    {
        $feature_to_order              = new FeatureElementToOrderInvolvedInChangeRepresentation();
        $feature_to_order->ids         = [456];
        $feature_to_order->direction   = 'after';
        $feature_to_order->compared_to = 123;

        $change = ContentChange::fromRESTRepresentation(null, $feature_to_order);

        self::assertNull($change->potential_feature_id_to_add);
        self::assertContainsEquals(456, $change->elements_to_order->ids);
        self::assertSame(123, $change->elements_to_order->compared_to);
        self::assertSame('after', $change->elements_to_order->direction);
    }
}
