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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;

final class NewArtifactLinkChangesetValueBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const REMOVED_ARTIFACT_ID          = 103;
    private const ADDED_ARTIFACT_ID            = 106;
    private const SECOND_UNCHANGED_ARTIFACT_ID = 102;
    private const FIRST_UNCHANGED_ARTIFACT_ID  = 101;
    private const PARENT_ARTIFACT_ID           = 100;
    private const FIELD_ID                     = 242;

    private function build(array $payload)
    {
        $builder = new NewArtifactLinkChangesetValueBuilder(
            RetrieveForwardLinksStub::withLinks(
                new CollectionOfForwardLinks([
                    ForwardLinkStub::withType(self::FIRST_UNCHANGED_ARTIFACT_ID, '_is_child'),
                    ForwardLinkStub::withType(self::SECOND_UNCHANGED_ARTIFACT_ID, '_is_child'),
                    ForwardLinkStub::withType(self::REMOVED_ARTIFACT_ID, '_is_child'),
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

        return $builder->buildFromPayload(
            ArtifactTestBuilder::anArtifact(1060)->build(),
            $link_field,
            UserTestBuilder::buildWithDefaults(),
            $payload
        );
    }

    public function testItBuildsFromARESTPayload(): void
    {
        $payload = [
            'links'  => [
                ['id' => self::FIRST_UNCHANGED_ARTIFACT_ID],
                ['id' => self::SECOND_UNCHANGED_ARTIFACT_ID, 'type' => '_is_child'],
                ['id' => self::ADDED_ARTIFACT_ID, 'type' => '_depends_on'],
            ],
            'parent' => ['id' => self::PARENT_ARTIFACT_ID],
        ];

        $update_value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $update_value->getFieldId());
        self::assertSame(self::PARENT_ARTIFACT_ID, $update_value->getParent()->getParentArtifactId());
        self::assertSame([self::ADDED_ARTIFACT_ID], $update_value->getArtifactLinksDiff()->getNewValues());
        self::assertSame([self::REMOVED_ARTIFACT_ID], $update_value->getArtifactLinksDiff()->getRemovedValues());
    }

    public function testItBuildsFromARESTPayloadWithOnlyParentKey(): void
    {
        $payload = [
            'parent' => ['id' => self::PARENT_ARTIFACT_ID],
        ];

        $update_value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $update_value->getFieldId());
        self::assertSame(self::PARENT_ARTIFACT_ID, $update_value->getParent()->getParentArtifactId());
        self::assertNull($update_value->getArtifactLinksDiff());
        self::assertNull($update_value->getSubmittedValues());
    }

    public function testItBuildsFromARESTPayloadWithOnlyLinksKey(): void
    {
        $payload = [
            'links' => [
                ['id' => self::FIRST_UNCHANGED_ARTIFACT_ID],
                ['id' => self::SECOND_UNCHANGED_ARTIFACT_ID, 'type' => '_is_child'],
                ['id' => self::ADDED_ARTIFACT_ID, 'type' => '_depends_on'],
            ],
        ];

        $update_value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $update_value->getFieldId());
        self::assertNull($update_value->getParent());
        self::assertSame([self::ADDED_ARTIFACT_ID], $update_value->getArtifactLinksDiff()->getNewValues());
        self::assertSame([self::REMOVED_ARTIFACT_ID], $update_value->getArtifactLinksDiff()->getRemovedValues());
        self::assertNotNull($update_value->getSubmittedValues());
        $submitted_values = $update_value->getSubmittedValues()->getArtifactLinks();
        self::assertCount(3, $submitted_values);
        [$first_link, $second_link, $third_link] = $submitted_values;
        self::assertSame(self::FIRST_UNCHANGED_ARTIFACT_ID, $first_link->getTargetArtifactId());
        self::assertNull($first_link->getType());
        self::assertSame(self::SECOND_UNCHANGED_ARTIFACT_ID, $second_link->getTargetArtifactId());
        self::assertSame('_is_child', $second_link->getType());
        self::assertSame(self::ADDED_ARTIFACT_ID, $third_link->getTargetArtifactId());
        self::assertSame('_depends_on', $third_link->getType());
    }

    public function dataProviderInvalidPayloads(): array
    {
        return [
            'Payload is empty'                                  => [[]],
            'Payload has none of the required keys'             => [['invalid_key' => []]],
            'Links key does not contain an array'               => [['links' => null]],
        ];
    }

    /**
     * @dataProvider dataProviderInvalidPayloads
     */
    public function testItThrowsWhenPayloadHasNoneOfTheRequiredKeys(array $payload): void
    {
        $this->expectException(\Tracker_FormElement_InvalidFieldValueException::class);
        $this->build($payload);
    }
}
