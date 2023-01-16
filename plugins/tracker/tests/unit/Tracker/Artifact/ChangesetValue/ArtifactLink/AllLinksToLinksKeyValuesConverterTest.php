<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation;

final class AllLinksToLinksKeyValuesConverterTest extends TestCase
{
    public function testItDoesNotChangeTheRepresentationWhenTheKeyAllLinksIsNotGiven(): void
    {
        $artifact_value        = new ArtifactValuesRepresentation();
        $artifact_value->links = ['id' => 12, 'type' => ''];
        $values                = [$artifact_value];

        self::assertSame($values, AllLinksToLinksKeyValuesConverter::convertIfNeeded($values));
    }

    public function testItConvertsTheRepresentation(): void
    {
        $all_links            = new LinkWithDirectionRepresentation();
        $all_links->id        = 12;
        $all_links->type      = '';
        $all_links->direction = 'forward';

        $artifact_value               = new ArtifactValuesRepresentation();
        $artifact_value->links        = null;
        $artifact_value->all_links[0] = $all_links;

        $values = [$artifact_value];

        $converted_value = AllLinksToLinksKeyValuesConverter::convertIfNeeded($values);

        self::assertNull($converted_value[0]->all_links);

        $expected_links_key_value[0] = ['id' => 12, 'type' => '', 'direction' => 'forward'];
        self::assertEquals($expected_links_key_value, $converted_value[0]->links);
    }
}
