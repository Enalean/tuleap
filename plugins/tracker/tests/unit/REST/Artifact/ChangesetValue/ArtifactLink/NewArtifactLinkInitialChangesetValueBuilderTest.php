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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink;

use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;

final class NewArtifactLinkInitialChangesetValueBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private const FIELD_ID                 = 405;
    private const FIRST_ADDED_ARTIFACT_ID  = 66;
    private const SECOND_ADDED_ARTIFACT_ID = 460;
    private const PARENT_ARTIFACT_ID       = 165;

    /**
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    private function build(ArtifactValuesRepresentation $payload): NewArtifactLinkInitialChangesetValue
    {
        $builder = new NewArtifactLinkInitialChangesetValueBuilder();
        return $builder->buildFromPayload(
            ArtifactLinkFieldBuilder::anArtifactLinkField(self::FIELD_ID)->build(),
            $payload
        );
    }

    public function testItBuildsFromARESTPayload(): void
    {
        $payload         = new ArtifactValuesRepresentation();
        $payload->links  = [
            ['id' => self::FIRST_ADDED_ARTIFACT_ID],
            ['id' => self::SECOND_ADDED_ARTIFACT_ID, 'type' => 'custom_type'],
        ];
        $payload->parent = ['id' => self::PARENT_ARTIFACT_ID];

        $value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertNotNull($value->getParent());
        self::assertSame(self::PARENT_ARTIFACT_ID, $value->getParent()->unwrapOr(null)?->getParentArtifactId());

        $new_links = $value->getNewLinks();
        self::assertCount(2, $new_links->getArtifactLinks());
        [$first_link, $second_link] = $new_links->getArtifactLinks();
        self::assertSame(self::FIRST_ADDED_ARTIFACT_ID, $first_link->getTargetArtifactId());
        self::assertSame(Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $first_link->getType());
        self::assertSame(self::SECOND_ADDED_ARTIFACT_ID, $second_link->getTargetArtifactId());
        self::assertSame('custom_type', $second_link->getType());
    }

    public function testItBuildsFromARESTPayloadWithOnlyParentKey(): void
    {
        $payload         = new ArtifactValuesRepresentation();
        $payload->parent = ['id' => self::PARENT_ARTIFACT_ID];

        $value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertNotNull($value->getParent());
        self::assertSame(self::PARENT_ARTIFACT_ID, $value->getParent()->unwrapOr(null)?->getParentArtifactId());
        self::assertEmpty($value->getNewLinks()->getArtifactLinks());
    }

    public function testItBuildsFromARESTPayloadWithOnlyLinksKey(): void
    {
        $payload        = new ArtifactValuesRepresentation();
        $payload->links = [
            ['id' => self::FIRST_ADDED_ARTIFACT_ID],
            ['id' => self::SECOND_ADDED_ARTIFACT_ID, 'type' => 'custom_type'],
        ];

        $value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertTrue($value->getParent()->isNothing());

        $new_links = $value->getNewLinks();
        self::assertCount(2, $new_links->getArtifactLinks());
        [$first_link, $second_link] = $new_links->getArtifactLinks();
        self::assertSame(self::FIRST_ADDED_ARTIFACT_ID, $first_link->getTargetArtifactId());
        self::assertSame(Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $first_link->getType());
        self::assertSame(self::SECOND_ADDED_ARTIFACT_ID, $second_link->getTargetArtifactId());
        self::assertSame('custom_type', $second_link->getType());
    }

    public function testItBuildsFromARESTPayloadWithOnlyAllLinksKey(): void
    {
        $link1_representation            = new LinkWithDirectionRepresentation();
        $link1_representation->id        = self::FIRST_ADDED_ARTIFACT_ID;
        $link1_representation->direction = "forward";
        $link1_representation->type      = "";

        $link2_representation            = new LinkWithDirectionRepresentation();
        $link2_representation->id        = self::SECOND_ADDED_ARTIFACT_ID;
        $link2_representation->direction = "reverse";
        $link2_representation->type      = "";

        $payload            = new ArtifactValuesRepresentation();
        $payload->all_links = [$link1_representation, $link2_representation];

        $value = $this->build($payload);

        self::assertSame(self::FIELD_ID, $value->getFieldId());
        self::assertTrue($value->getParent()->isNothing());

        $new_links = $value->getNewLinks();
        self::assertCount(1, $new_links->getArtifactLinks());
        [$first_link] = $new_links->getArtifactLinks();
        self::assertSame(self::FIRST_ADDED_ARTIFACT_ID, $first_link->getTargetArtifactId());
        self::assertSame(Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $first_link->getType());

        $reverse_links = $value->getReverseLinks();
        self::assertCount(1, $reverse_links->links);
        [$first_link] = $reverse_links->links;
        self::assertSame(self::SECOND_ADDED_ARTIFACT_ID, $first_link->getSourceArtifactId());
        self::assertSame(Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $first_link->getType());
    }
}
