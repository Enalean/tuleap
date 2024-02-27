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

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields;

use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class FormElementFactoryAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 99;
    private RetrieveFullTrackerStub $tracker_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tracker_FormElementFactory
     */
    private $form_element_factory;
    private TrackerIdentifierStub $tracker_identifier;

    protected function setUp(): void
    {
        $tracker                    = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->build();
        $this->tracker_retriever    = RetrieveFullTrackerStub::withTracker($tracker);
        $this->form_element_factory = $this->createStub(\Tracker_FormElementFactory::class);
        $this->tracker_identifier   = TrackerIdentifierStub::withId(self::TRACKER_ID);
    }

    private function getAdapter(): FormElementFactoryAdapter
    {
        return new FormElementFactoryAdapter($this->tracker_retriever, $this->form_element_factory);
    }

    public function testItReturnsTheArtifactLinkField(): void
    {
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(204)->build();
        $this->form_element_factory->method('getUsedArtifactLinkFields')->willReturn([$field]);

        $result = $this->getAdapter()->getArtifactLinkField($this->tracker_identifier);
        self::assertSame($field, $result);
    }

    public function testItReturnsNullWhenNoArtifactLinkFieldOrItIsUnused(): void
    {
        $this->form_element_factory->method('getUsedArtifactLinkFields')->willReturn([]);

        self::assertNull($this->getAdapter()->getArtifactLinkField($this->tracker_identifier));
    }
}
