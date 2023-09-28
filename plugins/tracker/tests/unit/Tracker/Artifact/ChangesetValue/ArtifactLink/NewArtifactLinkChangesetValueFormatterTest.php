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

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Option\Option;
use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\NewParentLinkStub;

final class NewArtifactLinkChangesetValueFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID = 993;
    private CollectionOfForwardLinks $already_linked;
    /** @var Option<CollectionOfForwardLinks> */
    private Option $submitted_links;
    /** @var Option<NewParentLink> */
    private Option $parent;

    protected function setUp(): void
    {
        $this->already_linked  = new CollectionOfForwardLinks([]);
        $this->submitted_links = Option::fromValue(new CollectionOfForwardLinks([]));
        $this->parent          = Option::nothing(NewParentLink::class);
    }

    private function format(): array
    {
        $value = NewArtifactLinkChangesetValue::fromParts(
            self::FIELD_ID,
            $this->already_linked,
            $this->submitted_links,
            $this->parent,
            new CollectionOfReverseLinks([])
        );
        return NewArtifactLinkChangesetValueFormatter::formatForWebUI($value);
    }

    public function testItFormatsNewValuesWithoutType(): void
    {
        $first_artifact_id     = 48;
        $second_artifact_id    = 53;
        $this->submitted_links = Option::fromValue(
            new CollectionOfForwardLinks([
                ForwardLinkStub::withNoType($first_artifact_id),
                ForwardLinkStub::withNoType($second_artifact_id),
            ])
        );
        $fields_data           = $this->format();
        self::assertArrayHasKey('new_values', $fields_data);
        self::assertSame('48,53', $fields_data['new_values']);
        self::assertCount(2, $fields_data['types']);
        self::assertArrayHasKey($first_artifact_id, $fields_data['types']);
        self::assertSame(\Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $fields_data['types'][$first_artifact_id]);
        self::assertArrayHasKey($second_artifact_id, $fields_data['types']);
        self::assertSame(\Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $fields_data['types'][$second_artifact_id]);
    }

    public function testItFormatsNewValuesWithType(): void
    {
        $first_artifact_id     = 48;
        $second_artifact_id    = 53;
        $this->submitted_links = Option::fromValue(
            new CollectionOfForwardLinks([
                ForwardLinkStub::withType($first_artifact_id, 'custom_type'),
                ForwardLinkStub::withType($second_artifact_id, '_covered_by'),
            ])
        );
        $fields_data           = $this->format();
        self::assertArrayHasKey('new_values', $fields_data);
        self::assertSame('48,53', $fields_data['new_values']);
        self::assertArrayHasKey('types', $fields_data);
        self::assertCount(2, $fields_data['types']);
        self::assertArrayHasKey($first_artifact_id, $fields_data['types']);
        self::assertSame('custom_type', $fields_data['types'][$first_artifact_id]);
        self::assertArrayHasKey($second_artifact_id, $fields_data['types']);
        self::assertSame('_covered_by', $fields_data['types'][$second_artifact_id]);
    }

    public function testItFormatsWhenNoNewValues(): void
    {
        $fields_data = $this->format();
        self::assertArrayHasKey('new_values', $fields_data);
        self::assertSame('', $fields_data['new_values']);
        self::assertArrayHasKey('types', $fields_data);
        self::assertEmpty($fields_data['types']);
    }

    public function testItFormatsParent(): void
    {
        $this->parent = Option::fromValue(NewParentLinkStub::withId(55));
        $fields_data  = $this->format();
        self::assertArrayHasKey('parent', $fields_data);
        self::assertCount(1, $fields_data['parent']);
        self::assertContains(55, $fields_data['parent']);
    }

    public function testItOmitsParentWhenItIsNothing(): void
    {
        $fields_data = $this->format();
        self::assertArrayNotHasKey('parent', $fields_data);
    }

    public function testItFormatsRemovedValues(): void
    {
        $first_artifact_id    = 18;
        $second_artifact_id   = 62;
        $this->already_linked = new CollectionOfForwardLinks([
            ForwardLinkStub::withType($first_artifact_id, '_is_child'),
            ForwardLinkStub::withNoType($second_artifact_id),
        ]);
        $fields_data          = $this->format();
        self::assertArrayHasKey('removed_values', $fields_data);
        self::assertArrayHasKey($first_artifact_id, $fields_data['removed_values']);
        self::assertCount(1, $fields_data['removed_values'][$first_artifact_id]);
        self::assertContains($first_artifact_id, $fields_data['removed_values'][$first_artifact_id]);
        self::assertArrayHasKey($second_artifact_id, $fields_data['removed_values']);
        self::assertCount(1, $fields_data['removed_values'][$second_artifact_id]);
        self::assertContains($second_artifact_id, $fields_data['removed_values'][$second_artifact_id]);
    }

    public function testItFormatsWhenNoRemovedValue(): void
    {
        $fields_data = $this->format();
        self::assertArrayHasKey('removed_values', $fields_data);
        self::assertEmpty($fields_data['removed_values']);
    }

    public function testItFormatsWhenNoChange(): void
    {
        $this->submitted_links = Option::nothing(CollectionOfForwardLinks::class);

        $fields_data = $this->format();
        self::assertArrayHasKey('new_values', $fields_data);
        self::assertEmpty($fields_data['new_values']);
        self::assertArrayHasKey('removed_values', $fields_data);
        self::assertEmpty($fields_data['removed_values']);
        self::assertArrayNotHasKey('types', $fields_data);
    }
}
