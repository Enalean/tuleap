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
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewParentLink;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;

final class InitialChangesetValuesContainerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID               = 745;
    private const FIELD_VALUE            = 'whatever';
    private const ARTIFACT_LINK_FIELD_ID = 992;
    /** @var Option<NewArtifactLinkInitialChangesetValue> $artifact_links */
    private Option $artifact_links;
    private array $fields_data;

    protected function setUp(): void
    {
        $this->fields_data    = [self::FIELD_ID => self::FIELD_VALUE];
        $this->artifact_links = Option::nothing(NewArtifactLinkInitialChangesetValue::class);
    }

    private function build(): InitialChangesetValuesContainer
    {
        return new InitialChangesetValuesContainer($this->fields_data, $this->artifact_links);
    }

    public function testItBuildsWithNoArtifactLinkValue(): void
    {
        $changeset_values = $this->build();
        self::assertSame($this->artifact_links, $changeset_values->getArtifactLinkValue());
        self::assertSame($this->fields_data, $changeset_values->getFieldsData());
    }

    public function testItBuildsWithArtifactLinkValue(): void
    {
        $submitted_links      = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(41, 'custom_type'),
            ForwardLinkStub::withNoType(91),
        ]);
        $this->artifact_links = Option::fromValue(
            NewArtifactLinkInitialChangesetValue::fromParts(
                self::ARTIFACT_LINK_FIELD_ID,
                $submitted_links,
                Option::nothing(NewParentLink::class),
                new CollectionOfReverseLinks([]),
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
                'new_values' => '41,91',
                'types'      => [
                    41 => 'custom_type',
                    91 => \Tracker_FormElement_Field_ArtifactLink::NO_TYPE,
                ],
            ],
            $new_fields_data[self::ARTIFACT_LINK_FIELD_ID]
        );
    }
}
