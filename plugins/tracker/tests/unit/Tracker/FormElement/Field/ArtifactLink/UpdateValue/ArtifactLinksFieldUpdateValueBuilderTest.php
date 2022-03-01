<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\UpdateValue;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksInfoStub;

final class ArtifactLinksFieldUpdateValueBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildALinkFieldUpdateValueFromARESTPayload(): void
    {
        $payload = [
            'links' => [
                ['id' => 101, 'type' => '_is_child'],
                ['id' => 102, 'type' => '_is_child'],
                ['id' => 106, 'type' => '_depends_on'],
            ],
            'parent' => ['id' => 100, 'direction' => 'reverse', 'type' => '_is_child'],
        ];

        $update_value = $this->buildUpdateValue($payload);

        self::assertEquals(100, $update_value->getParentArtifactLink()->id);
        self::assertEquals([106], $update_value->getArtifactLinksDiff()->getNewValues());
        self::assertEquals([103], $update_value->getArtifactLinksDiff()->getRemovedValues());
    }

    public function testItBuildsFromARESTPayloadWithoutALinksKey(): void
    {
        $payload = [
            'parent' => ['id' => 100, 'direction' => 'reverse', 'type' => '_is_child'],
        ];

        $update_value = $this->buildUpdateValue($payload);

        self::assertEquals(100, $update_value->getParentArtifactLink()->id);
        self::assertNull($update_value->getArtifactLinksDiff());
        self::assertNull($update_value->getSubmittedValues());
    }

    public function testItBuildsFromARESTPayloadWithoutAParentKey(): void
    {
        $payload = [
            'links' => [
                ['id' => 101, 'type' => '_is_child'],
                ['id' => 102, 'type' => '_is_child'],
                ['id' => 106, 'type' => '_depends_on'],
            ],
        ];

        $update_value = $this->buildUpdateValue($payload);

        self::assertNull($update_value->getParentArtifactLink());
        self::assertEquals([106], $update_value->getArtifactLinksDiff()->getNewValues());
        self::assertEquals([103], $update_value->getArtifactLinksDiff()->getRemovedValues());
    }


    /**
     * @return MockObject & \Tracker_FormElement_Field_ArtifactLink
     */
    private function getMockedLinkField()
    {
        $link_field = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $link_field->method('getId')->willReturn(6666666);
        return $link_field;
    }

    private function buildUpdateValue(array $payload)
    {
        $builder = new ArtifactLinksFieldUpdateValueBuilder(
            new ArtifactLinksPayloadStructureChecker(),
            new ArtifactLinksPayloadExtractor(),
            new ArtifactParentLinkPayloadExtractor(),
            RetrieveForwardLinksInfoStub::withLinksInfo()
        );

        return $builder->buildArtifactLinksFieldUpdateValue(
            UserTestBuilder::buildWithDefaults(),
            $this->getMockedLinkField(),
            $payload,
            ArtifactTestBuilder::anArtifact(1060)->build()
        );
    }
}
