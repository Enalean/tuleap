<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusFieldRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 77;
    private ?\Tracker_FormElement_Field_Selectbox $field;

    protected function setUp(): void
    {
        $this->field = new \Tracker_FormElement_Field_Selectbox(
            848,
            self::TRACKER_ID,
            1,
            'status',
            'Status',
            'Irrelevant',
            true,
            'P',
            false,
            '',
            1
        );
    }

    public function getStatusField(): ?\Tracker_FormElement_Field_List
    {
        $tracker = TrackerTestBuilder::aTracker()->build();

        $semantic = $this->createStub(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::class);
        $semantic->method('getField')->willReturn($this->field);

        $factory = $this->createStub(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory::class);
        $factory->method('getByTracker')->willReturn($semantic);

        $retriever = new StatusFieldRetriever($factory);
        return $retriever->getStatusField($tracker);
    }

    public function testItRetrievesStatusField(): void
    {
        self::assertSame($this->field, $this->getStatusField());
    }

    public function testItReturnsNullWhenStatusIsNotConfigured(): void
    {
        $this->field = null;
        self::assertNull($this->getStatusField());
    }
}
