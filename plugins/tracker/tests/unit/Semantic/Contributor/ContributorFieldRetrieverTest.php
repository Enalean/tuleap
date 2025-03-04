<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Contributor;

use Tracker_Semantic_Contributor;
use Tracker_Semantic_ContributorFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ContributorFieldRetrieverTest extends TestCase
{
    protected function tearDown(): void
    {
        Tracker_Semantic_Contributor::clearInstances();
    }

    public function testItRetrieveFieldGivenByFactory(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $field   = ListFieldBuilder::aListField(103)->build();
        Tracker_Semantic_Contributor::setInstance(
            new Tracker_Semantic_Contributor($tracker, $field),
            $tracker,
        );
        $retriever = new ContributorFieldRetriever(Tracker_Semantic_ContributorFactory::instance());
        self::assertSame($field, $retriever->getContributorField($tracker));
    }
}
