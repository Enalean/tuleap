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

use Tuleap\Tracker\Test\Stub\ForwardLinkStub;
use Tuleap\Tracker\Test\Stub\NewParentLinkStub;

final class NewArtifactLinkInitialChangesetValueFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIELD_ID = 675;
    private CollectionOfForwardLinks $new_links;
    private ?NewParentLink $parent;

    protected function setUp(): void
    {
        $this->new_links = new CollectionOfForwardLinks([]);
        $this->parent    = null;
    }

    private function format(): array
    {
        $value = NewArtifactLinkInitialChangesetValue::fromParts(
            self::FIELD_ID,
            $this->new_links,
            $this->parent
        );
        return NewArtifactLinkInitialChangesetValueFormatter::formatForWebUI($value);
    }

    public function testItFormatsNewLinksWithoutType(): void
    {
        $this->new_links = new CollectionOfForwardLinks([
            ForwardLinkStub::withNoType(24),
            ForwardLinkStub::withNoType(45),
        ]);
        $field_data      = $this->format();
        self::assertArrayHasKey('new_values', $field_data);
        self::assertSame('24,45', $field_data['new_values']);
        self::assertCount(0, $field_data['types']);
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
        $this->parent = NewParentLinkStub::withId(71);
        $fields_data  = $this->format();
        self::assertArrayHasKey('parent', $fields_data);
        self::assertCount(1, $fields_data['parent']);
        self::assertContains(71, $fields_data['parent']);
    }

    public function testItOmitsParentWhenItIsNull(): void
    {
        $fields_data = $this->format();
        self::assertArrayNotHasKey('parent', $fields_data);
    }
}
