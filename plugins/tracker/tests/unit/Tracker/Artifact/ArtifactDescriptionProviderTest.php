<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElement_Field_Text;
use Tracker_Semantic_Description;

class ArtifactDescriptionProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Semantic_Description
     */
    private $semantic_description;
    /**
     * @var ArtifactDescriptionProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->semantic_description = Mockery::mock(Tracker_Semantic_Description::class);
        $this->provider             = new ArtifactDescriptionProvider($this->semantic_description);
    }

    public function testGetDescriptionReturnNullIfNoFieldInSemantic(): void
    {
        $this->semantic_description->shouldReceive('getField')->once()->andReturnNull();

        $this->assertEquals('', $this->provider->getDescription(Mockery::mock(Tracker_Artifact::class)));
    }

    public function testGetDescriptionReturnNullIfUserCannotReadTheField(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->shouldReceive('getField')->once()->andReturn($field);

        $field->shouldReceive('userCanRead')->once()->andReturnFalse();

        $this->assertEquals('', $this->provider->getDescription(Mockery::mock(Tracker_Artifact::class)));
    }

    public function testGetDescriptionReturnNullIfThereIsNoLastChangeset(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->shouldReceive('getField')->once()->andReturn($field);

        $field->shouldReceive('userCanRead')->once()->andReturnTrue();

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getLastChangeset')->once()->andReturnNull();

        $this->assertEquals('', $this->provider->getDescription($artifact));
    }

    public function testGetDescriptionReturnNullIfNoValueForField(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->shouldReceive('getField')->once()->andReturn($field);

        $field->shouldReceive('userCanRead')->once()->andReturnTrue();

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $artifact  = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getLastChangeset')->once()->andReturn($changeset);

        $changeset->shouldReceive('getValue')->with($field)->once()->andReturnNull();

        $this->assertEquals('', $this->provider->getDescription($artifact));
    }

    public function testGetDescriptionReturnTheDescriptionAsPlainText(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $this->semantic_description->shouldReceive('getField')->once()->andReturn($field);

        $field->shouldReceive('userCanRead')->once()->andReturnTrue();

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $artifact  = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getLastChangeset')->once()->andReturn($changeset);

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_Text::class);
        $changeset->shouldReceive('getValue')->with($field)->once()->andReturn($changeset_value);

        $changeset_value->shouldReceive('getContentAsText')->once()->andReturn('The description');

        $this->assertEquals('The description', $this->provider->getDescription($artifact));
    }
}
