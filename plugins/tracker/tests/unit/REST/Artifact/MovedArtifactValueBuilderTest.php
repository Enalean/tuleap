<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\Test\Builders\ArtifactValuesRepresentationBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MovedArtifactValueBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MovedArtifactValueBuilder $builder;

    private Artifact&MockObject $artifact;

    private Tracker&MockObject $tracker;

    private StringField $field_string;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder      = new MovedArtifactValueBuilder();
        $this->artifact     = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->tracker      = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $this->field_string = StringFieldBuilder::aStringField(101)->build();
    }

    public function testItThrowsAnExceptionIfArtifactHasNoTitle(): void
    {
        $this->artifact->method('getTitle')->willReturn(null);
        $this->tracker->method('getTitleField')->willReturn($this->field_string);

        $this->expectException(\Tuleap\Tracker\Exception\SemanticTitleNotDefinedException::class);

        $this->builder->getValues($this->artifact, $this->tracker);
    }

    public function testItThrowsAnExceptionIfTrackerHasNoTitle(): void
    {
        $this->artifact->method('getTitle')->willReturn('title');
        $this->tracker->method('getTitleField')->willReturn(null);

        $this->expectException(\Tuleap\Tracker\Exception\SemanticTitleNotDefinedException::class);

        $this->builder->getValues($this->artifact, $this->tracker);
    }

    public function testItBuildsArtifactValuesRepresentation(): void
    {
        $this->artifact->method('getTitle')->willReturn('title');
        $this->tracker->method('getTitleField')->willReturn($this->field_string);

        $values = $this->builder->getValues($this->artifact, $this->tracker);

        $representation = ArtifactValuesRepresentationBuilder::aRepresentation(101)->withValue('title')->build();

        $expected = [
            $representation,
        ];

        $this->assertEquals($expected, $values);
    }
}
