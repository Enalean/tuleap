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

use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactValuesRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\LinkWithDirectionRepresentationBuilder;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\RetrieveForwardLinksStub;

final class NewArtifactLinkChangesetValueBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private const REMOVED_ARTIFACT_ID          = 103;
    private const ADDED_ARTIFACT_ID            = 106;
    private const SECOND_UNCHANGED_ARTIFACT_ID = 102;
    private const FIRST_UNCHANGED_ARTIFACT_ID  = 101;
    private const PARENT_ARTIFACT_ID           = 100;
    private const FIELD_ID                     = 242;

    private function build(ArtifactValuesRepresentation $payload)
    {
        $builder = new NewArtifactLinkChangesetValueBuilder(
            RetrieveForwardLinksStub::withLinks(
                new CollectionOfForwardLinks([
                    ForwardLinkStub::withType(self::FIRST_UNCHANGED_ARTIFACT_ID, '_is_child'),
                    ForwardLinkStub::withType(self::SECOND_UNCHANGED_ARTIFACT_ID, '_is_child'),
                    ForwardLinkStub::withType(self::REMOVED_ARTIFACT_ID, '_is_child'),
                ])
            ),
        );

        return $builder->buildFromPayload(
            ArtifactTestBuilder::anArtifact(1060)->build(),
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::FIELD_ID)->build(),
            UserTestBuilder::buildWithDefaults(),
            $payload
        );
    }

    public function testItBuildsFromARESTPayload(): void
    {
        $payload = ArtifactValuesRepresentationBuilder::aRepresentation(self::FIELD_ID)
            ->withLinks(
                ['id' => self::FIRST_UNCHANGED_ARTIFACT_ID],
                ['id' => self::SECOND_UNCHANGED_ARTIFACT_ID, 'type' => '_is_child'],
                ['id' => self::ADDED_ARTIFACT_ID, 'type' => '_depends_on']
            )->withParent(self::PARENT_ARTIFACT_ID)
            ->build();

        $update_value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $update_value->getFieldId());
        self::assertSame(self::PARENT_ARTIFACT_ID, $update_value->getParent()->unwrapOr(null)?->getParentArtifactId());
        self::assertSame([self::ADDED_ARTIFACT_ID], $update_value->getAddedValues()->getTargetArtifactIds());
        self::assertSame([self::REMOVED_ARTIFACT_ID], $update_value->getRemovedValues()->getTargetArtifactIds());
    }

    public function testItBuildsFromARESTPayloadWithOnlyParentKey(): void
    {
        $payload = ArtifactValuesRepresentationBuilder::aRepresentation(self::FIELD_ID)
            ->withParent(self::PARENT_ARTIFACT_ID)
            ->build();

        $update_value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $update_value->getFieldId());
        self::assertSame(self::PARENT_ARTIFACT_ID, $update_value->getParent()->unwrapOr(null)->getParentArtifactId());
        self::assertEmpty($update_value->getAddedValues()->getArtifactLinks());
        self::assertEmpty($update_value->getRemovedValues()->getArtifactLinks());
        self::assertTrue($update_value->getSubmittedValues()->isNothing());
    }

    public function testItBuildsFromARESTPayloadWithOnlyLinksKey(): void
    {
        $payload = ArtifactValuesRepresentationBuilder::aRepresentation(self::FIELD_ID)
            ->withLinks(
                ['id' => self::FIRST_UNCHANGED_ARTIFACT_ID],
                ['id' => self::SECOND_UNCHANGED_ARTIFACT_ID, 'type' => '_is_child'],
                ['id' => self::ADDED_ARTIFACT_ID, 'type' => '_depends_on']
            )->build();

        $update_value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $update_value->getFieldId());
        self::assertTrue($update_value->getParent()->isNothing());
        self::assertSame([self::ADDED_ARTIFACT_ID], $update_value->getAddedValues()->getTargetArtifactIds());
        self::assertSame([self::REMOVED_ARTIFACT_ID], $update_value->getRemovedValues()->getTargetArtifactIds());
        $submitted_values = $update_value->getSubmittedValues()->unwrapOr(new CollectionOfForwardLinks([]))->getArtifactLinks();
        self::assertCount(3, $submitted_values);
        [$first_link, $second_link, $third_link] = $submitted_values;
        self::assertSame(self::FIRST_UNCHANGED_ARTIFACT_ID, $first_link->getTargetArtifactId());
        self::assertSame(Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $first_link->getType());
        self::assertSame(self::SECOND_UNCHANGED_ARTIFACT_ID, $second_link->getTargetArtifactId());
        self::assertSame('_is_child', $second_link->getType());
        self::assertSame(self::ADDED_ARTIFACT_ID, $third_link->getTargetArtifactId());
        self::assertSame('_depends_on', $third_link->getType());
    }

    public function testItBuildsFromARESTPayloadWithReverseLinks(): void
    {
        $payload = ArtifactValuesRepresentationBuilder::aRepresentation(self::FIELD_ID)
            ->withAllLinks(LinkWithDirectionRepresentationBuilder::aReverseLink(48)->build())
            ->build();

        $update_value = $this->build($payload);

        $this->assertSame(48, $update_value->getSubmittedReverseLinks()->links[0]->getSourceArtifactId());
    }
}
