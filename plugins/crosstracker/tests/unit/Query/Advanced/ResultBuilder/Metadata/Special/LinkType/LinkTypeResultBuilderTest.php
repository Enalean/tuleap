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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\ArtifactLinkTypeRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\LinkDirection;
use Tuleap\Tracker\REST\v1\TrackerFieldRepresentations\LinkTypeRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ArtifactLink\Type\RetrieveSystemTypePresenterStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinkTypeResultBuilderTest extends TestCase
{
    private Artifact $artifact;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact = ArtifactTestBuilder::anArtifact(223)->withTitle('My artifact')->build();
    }

    #[DataProvider('dataProviderReverseAndForwardDirection')]
    public function testItBuildALinkedTypeRepresentationBasedArtifactLinkRepresentation(
        LinkDirection $direction,
        string $label,
        string $link_type,
    ): void {
        $select_results = [
            ['id' => $this->artifact->getId(), '@link_type' => $link_type, $direction->value => $label],
        ];

        $link_type_builder = new LinkTypeResultBuilder(RetrieveSystemTypePresenterStub::build());
        $result            = $link_type_builder->getResult(
            $select_results,
            Option::fromValue($direction)
        );

        $link_type_representation = new LinkTypeRepresentation(
            $link_type,
            $direction->value,
            $label,
            sprintf(
                'artifacts/%s/linked_artifacts?nature=%s&direction=%s',
                $this->artifact->getId(),
                $link_type,
                $direction->value
            )
        );

        $expected = new SelectedValue('@link_type', ArtifactLinkTypeRepresentation::build($link_type_representation));
        self::assertEqualsCanonicalizing($expected, $result->values[$this->artifact->getId()]);
    }

    public static function dataProviderReverseAndForwardDirection(): array
    {
        return [
            'forward _is_child' => [LinkDirection::FORWARD, 'Child', ArtifactLinkField::TYPE_IS_CHILD],
            'reverse _is_child' => [LinkDirection::REVERSE, 'Parent', ArtifactLinkField::TYPE_IS_CHILD],
            'forward custom' => [LinkDirection::FORWARD, 'Is custom', 'custom'],
            'reverse custom' => [LinkDirection::REVERSE, 'From custom', 'custom'],
        ];
    }

    public static function dataProviderReverseAndForwardForNoType(): array
    {
        return [
            'forward' => [LinkDirection::FORWARD, 'is Linked to'],
            'reverse' => [LinkDirection::REVERSE, 'is Linked from'],
        ];
    }

    #[DataProvider('dataProviderReverseAndForwardForNoType')]
    public function testItBuildALinkedTypeRepresentationWhenLinkIsNotTyped(LinkDirection $direction, string $expected_label): void
    {
        $select_results = [
            ['id' => $this->artifact->getId(), '@link_type' => ArtifactLinkField::DEFAULT_LINK_TYPE, $direction->value => ''],
        ];

        $link_type_builder = new LinkTypeResultBuilder(RetrieveSystemTypePresenterStub::build());
        $result            = $link_type_builder->getResult(
            $select_results,
            Option::fromValue($direction)
        );

        $link_type_representation = new LinkTypeRepresentation(ArtifactLinkField::DEFAULT_LINK_TYPE, $direction->value, $expected_label, '');

        $expected = new SelectedValue('@link_type', ArtifactLinkTypeRepresentation::build($link_type_representation));
        self::assertEqualsCanonicalizing($expected, $result->values[$this->artifact->getId()]);
    }
}
