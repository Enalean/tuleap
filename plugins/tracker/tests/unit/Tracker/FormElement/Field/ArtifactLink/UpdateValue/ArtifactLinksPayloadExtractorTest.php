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

final class ArtifactLinksPayloadExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExtractsArtifactLinksFromPayloadAndReturnsACollection(): void
    {
        $link_1  = ['id' => 101, 'type' => '_is_child'];
        $link_2  = ['id' => 102, 'type' => '_depends_on'];
        $link_3  = ['id' => 103, 'type' => '_duplicates'];
        $payload = [
            'links' => [
                $link_1,
                $link_2,
                $link_3,
            ],
        ];

        $collection = (new ArtifactLinksPayloadExtractor())->extractValuesFromPayload($payload);

        self::assertEquals(
            [
                ArtifactLink::fromPayload($link_1),
                ArtifactLink::fromPayload($link_2),
                ArtifactLink::fromPayload($link_3),
            ],
            $collection->getArtifactLinks()
        );
    }
}
