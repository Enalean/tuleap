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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\LinkStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;

final class ArtifactLinksFieldUpdateValueBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const REMOVED_ARTIFACT_ID          = 103;
    private const ADDED_ARTIFACT_ID            = 106;
    private const SECOND_UNCHANGED_ARTIFACT_ID = 102;
    private const FIRST_UNCHANGED_ARTIFACT_ID  = 101;
    private const PARENT_ARTIFACT_ID           = 100;
    private const FIELD_ID                     = 242;

    private function buildUpdateValue(array $payload)
    {
        $builder = new ArtifactLinksFieldUpdateValueBuilder(
            new ArtifactLinksPayloadStructureChecker(),
            new ArtifactLinksPayloadExtractor(),
            new ArtifactParentLinkPayloadExtractor(),
            RetrieveForwardLinksStub::withLinks(
                new CollectionOfArtifactLinks([
                    LinkStub::withType(self::FIRST_UNCHANGED_ARTIFACT_ID, '_is_child'),
                    LinkStub::withType(self::SECOND_UNCHANGED_ARTIFACT_ID, '_is_child'),
                    LinkStub::withType(self::REMOVED_ARTIFACT_ID, '_is_child'),
                ])
            )
        );

        $link_field = new \Tracker_FormElement_Field_ArtifactLink(
            self::FIELD_ID,
            55,
            1,
            'irrelevant',
            'Irrelevant',
            'Irrelevant',
            true,
            'P',
            false,
            '',
            1
        );

        return $builder->buildArtifactLinksFieldUpdateValue(
            UserTestBuilder::buildWithDefaults(),
            $link_field,
            $payload,
            ArtifactTestBuilder::anArtifact(1060)->build()
        );
    }

    public function testItBuildALinkFieldUpdateValueFromARESTPayload(): void
    {
        $payload = [
            'links'  => [
                ['id' => self::FIRST_UNCHANGED_ARTIFACT_ID, 'type' => '_is_child'],
                ['id' => self::SECOND_UNCHANGED_ARTIFACT_ID, 'type' => '_is_child'],
                ['id' => self::ADDED_ARTIFACT_ID, 'type' => '_depends_on'],
            ],
            'parent' => ['id' => self::PARENT_ARTIFACT_ID, 'direction' => 'reverse', 'type' => '_is_child'],
        ];

        $update_value = $this->buildUpdateValue($payload);

        self::assertSame(self::FIELD_ID, $update_value->getFieldId());
        self::assertSame(self::PARENT_ARTIFACT_ID, $update_value->getParentArtifactLink()->getTargetArtifactId());
        self::assertSame([self::ADDED_ARTIFACT_ID], $update_value->getArtifactLinksDiff()->getNewValues());
        self::assertSame([self::REMOVED_ARTIFACT_ID], $update_value->getArtifactLinksDiff()->getRemovedValues());
    }

    public function testItBuildsFromARESTPayloadWithoutALinksKey(): void
    {
        $payload = [
            'parent' => ['id' => self::PARENT_ARTIFACT_ID, 'direction' => 'reverse', 'type' => '_is_child'],
        ];

        $update_value = $this->buildUpdateValue($payload);

        self::assertSame(self::FIELD_ID, $update_value->getFieldId());
        self::assertSame(self::PARENT_ARTIFACT_ID, $update_value->getParentArtifactLink()->getTargetArtifactId());
        self::assertNull($update_value->getArtifactLinksDiff());
        self::assertNull($update_value->getSubmittedValues());
    }

    public function testItBuildsFromARESTPayloadWithoutAParentKey(): void
    {
        $payload = [
            'links' => [
                ['id' => self::FIRST_UNCHANGED_ARTIFACT_ID, 'type' => '_is_child'],
                ['id' => self::SECOND_UNCHANGED_ARTIFACT_ID, 'type' => '_is_child'],
                ['id' => self::ADDED_ARTIFACT_ID, 'type' => '_depends_on'],
            ],
        ];

        $update_value = $this->buildUpdateValue($payload);

        self::assertSame(self::FIELD_ID, $update_value->getFieldId());
        self::assertNull($update_value->getParentArtifactLink());
        self::assertSame([self::ADDED_ARTIFACT_ID], $update_value->getArtifactLinksDiff()->getNewValues());
        self::assertSame([self::REMOVED_ARTIFACT_ID], $update_value->getArtifactLinksDiff()->getRemovedValues());
    }
}
