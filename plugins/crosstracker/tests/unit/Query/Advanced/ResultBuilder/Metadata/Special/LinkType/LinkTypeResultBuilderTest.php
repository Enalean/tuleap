<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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


namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\LinkType;

use PHPUnit\Framework\TestCase;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\TextResultRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinkTypeResultBuilderTest extends TestCase
{
    private Artifact $artifact;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact = ArtifactTestBuilder::anArtifact(223)->withTitle('My artifact')->build();
    }

    public function testItBuildALinkedTypeRepresentationBasedTextRepresentation(): void
    {
        $select_results = [
            ['id' => $this->artifact->getId(), '@link_type' => '_is_child'],
        ];

        $link_type_builder = new LinkTypeResultBuilder();
        $result            = $link_type_builder->getResult(
            $select_results,
        );

        $expected = new SelectedValue('@link_type', new TextResultRepresentation('_is_child'));
        self::assertEqualsCanonicalizing($expected, $result->values[$this->artifact->getId()]);
    }
}
