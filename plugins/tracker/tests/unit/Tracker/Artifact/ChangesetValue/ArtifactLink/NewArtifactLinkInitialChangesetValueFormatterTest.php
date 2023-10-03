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

final class NewArtifactLinkInitialChangesetValueFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID = 675;
    private CollectionOfForwardLinks $new_links;
    /** @var Option<NewParentLink> */
    private Option $parent;
    private CollectionOfReverseLinks $reverse_links;

    protected function setUp(): void
    {
        $this->new_links     = new CollectionOfForwardLinks([]);
        $this->reverse_links = new CollectionOfReverseLinks([]);
        $this->parent        = Option::nothing(NewParentLink::class);
    }

    private function format(): array
    {
        $value = NewArtifactLinkInitialChangesetValue::fromParts(
            self::FIELD_ID,
            $this->new_links,
            $this->parent,
            $this->reverse_links
        );
        return NewArtifactLinkInitialChangesetValueFormatter::formatForWebUI($value);
    }

    public function testItFormatsNewLinksWithoutType(): void
    {
        $first_artifact_id  = 24;
        $second_artifact_id = 45;
        $this->new_links    = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType($first_artifact_id),
            ForwardLinkStub::withNoType($second_artifact_id),
        ]);
        $field_data         = $this->format();
        self::assertArrayHasKey('new_values', $field_data);
        self::assertSame('24,45', $field_data['new_values']);
        self::assertCount(2, $field_data['types']);
        self::assertArrayHasKey($first_artifact_id, $field_data['types']);
        self::assertSame(\Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $field_data['types'][$first_artifact_id]);
        self::assertArrayHasKey($second_artifact_id, $field_data['types']);
        self::assertSame(\Tracker_FormElement_Field_ArtifactLink::NO_TYPE, $field_data['types'][$second_artifact_id]);
    }

    public function testItFormatsNewLinksWithTypes(): void
    {
        $first_artifact_id  = 70;
        $second_artifact_id = 42;
        $this->new_links    = new CollectionOfForwardLinks([
            ForwardLinkStub::withType($first_artifact_id, 'custom_type'),
            ForwardLinkStub::withType($second_artifact_id, '_covered_by'),
        ]);
        $field_data         = $this->format();
        self::assertArrayHasKey('new_values', $field_data);
        self::assertSame('70,42', $field_data['new_values']);
        self::assertArrayHasKey('types', $field_data);
        self::assertCount(2, $field_data['types']);
        self::assertArrayHasKey($first_artifact_id, $field_data['types']);
        self::assertSame('custom_type', $field_data['types'][$first_artifact_id]);
        self::assertArrayHasKey($second_artifact_id, $field_data['types']);
        self::assertSame('_covered_by', $field_data['types'][$second_artifact_id]);
    }

    public function testItFormatsWhenNoNewValues(): void
    {
        $field_data = $this->format();
        self::assertArrayHasKey('new_values', $field_data);
        self::assertSame('', $field_data['new_values']);
        self::assertArrayHasKey('types', $field_data);
        self::assertEmpty($field_data['types']);
    }

    public function testItFormatsParent(): void
    {
        $this->parent = Option::fromValue(NewParentLinkStub::withId(71));
        $fields_data  = $this->format();
        self::assertArrayHasKey('parent', $fields_data);
        self::assertCount(1, $fields_data['parent']);
        self::assertContains(71, $fields_data['parent']);
    }

    public function testItOmitsParentWhenItIsNothing(): void
    {
        $fields_data = $this->format();
        self::assertArrayNotHasKey('parent', $fields_data);
    }
}
