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

namespace Tuleap\Tracker\Artifact\ChangesetValue;

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangeForwardLinksCommand;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\NewParentLinkStub;

final class ChangesetValuesContainerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID               = 170;
    private const FIELD_VALUE            = 'whatever';
    private const ARTIFACT_LINK_FIELD_ID = 219;
    /** @var Option<NewArtifactLinkChangesetValue> $artifact_links */
    private Option $artifact_links;
    private array $fields_data;

    protected function setUp(): void
    {
        $this->fields_data    = [self::FIELD_ID => self::FIELD_VALUE];
        $this->artifact_links = Option::nothing(NewArtifactLinkChangesetValue::class);
    }

    private function build(): ChangesetValuesContainer
    {
        return new ChangesetValuesContainer($this->fields_data, $this->artifact_links);
    }

    public function testItBuildsWithNoArtifactLinkValue(): void
    {
        $changeset_values = $this->build();
        self::assertSame($this->artifact_links, $changeset_values->getArtifactLinkValue());
        self::assertSame($this->fields_data, $changeset_values->getFieldsData());
    }

    public function testItBuildsWithArtifactLinkValue(): void
    {
        $submitted_links      = Option::fromValue(
            new CollectionOfForwardLinks([
                ForwardLinkStub::withType(99, 'custom_type'),
                ForwardLinkStub::withNoType(42),
            ])
        );
        $existing_links       = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(53, '_is_child'),
            ForwardLinkStub::withNoType(34),
        ]);
        $this->artifact_links = Option::fromValue(
            NewArtifactLinkChangesetValue::fromParts(
                ChangeForwardLinksCommand::fromSubmittedAndExistingLinks(
                    self::ARTIFACT_LINK_FIELD_ID,
                    $submitted_links,
                    $existing_links
                ),
                Option::fromValue(NewParentLinkStub::withId(63)),
                new CollectionOfReverseLinks([])
            )
        );
        $changeset_values     = $this->build();

        self::assertSame($this->artifact_links, $changeset_values->getArtifactLinkValue());
        $new_fields_data = $changeset_values->getFieldsData();
        self::assertNotSame($this->fields_data, $new_fields_data);
        self::assertArrayHasKey(self::FIELD_ID, $new_fields_data);
        self::assertSame(self::FIELD_VALUE, $new_fields_data[self::FIELD_ID]);
        self::assertArrayHasKey(self::ARTIFACT_LINK_FIELD_ID, $new_fields_data);
        self::assertSame(
            [
                'new_values'     => '99,42',
                'removed_values' => [53 => [53], 34 => [34]],
                'types'          => [
                    99 => 'custom_type',
                    42 => \Tracker_FormElement_Field_ArtifactLink::NO_TYPE,
                ],
                'parent'         => [63],
            ],
            $new_fields_data[self::ARTIFACT_LINK_FIELD_ID]
        );
    }
}
