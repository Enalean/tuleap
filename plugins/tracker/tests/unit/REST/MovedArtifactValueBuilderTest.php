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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_FormElement_Field_String;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class MovedArtifactValueBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var  MovedArtifactValueBuilder */
    private $builder;

    /** @var  Tracker_Artifact */
    private $artifact;

    /** @var  Tracker */
    private $tracker;

    /** @var Tracker_FormElement_Field_String */
    private $field_string;

    protected function setUp(): void
    {
        $this->builder      = new MovedArtifactValueBuilder();
        $this->artifact     = \Mockery::spy(\Tracker_Artifact::class);
        $this->tracker      = \Mockery::spy(\Tracker::class);
        $this->field_string = \Mockery::spy(\Tracker_FormElement_Field_String::class)->shouldReceive('getId')->andReturns(101)->getMock();
    }

    public function testItThrowsAnExceptionIfArtifactHasNoTitle(): void
    {
        $this->artifact->shouldReceive('getTitle')->andReturns(null);
        $this->tracker->shouldReceive('getTitleField')->andReturns($this->field_string);

        $this->expectException(\Tuleap\Tracker\Exception\SemanticTitleNotDefinedException::class);

        $this->builder->getValues($this->artifact, $this->tracker);
    }

    public function testItThrowsAnExceptionIfTrackerHasNoTitle(): void
    {
        $this->artifact->shouldReceive('getTitle')->andReturns("title");
        $this->tracker->shouldReceive('getTitleField')->andReturns(null);

        $this->expectException(\Tuleap\Tracker\Exception\SemanticTitleNotDefinedException::class);

        $this->builder->getValues($this->artifact, $this->tracker);
    }

    public function testItBuildsArtifactValuesRepresentation(): void
    {
        $this->artifact->shouldReceive('getTitle')->andReturns("title");
        $this->tracker->shouldReceive('getTitleField')->andReturns($this->field_string);

        $values = $this->builder->getValues($this->artifact, $this->tracker);

        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = 101;
        $representation->value    = "title";

        $expected = array(
            $representation
        );

        $this->assertEquals($expected, $values);
    }
}
