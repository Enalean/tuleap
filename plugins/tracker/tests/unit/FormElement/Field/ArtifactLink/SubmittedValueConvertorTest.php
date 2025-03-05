<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_ArtifactLinkInfo;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SubmittedValueConvertorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SubmittedValueConvertor
     */
    private $convertor;

    /** @var Tracker_Artifact_ChangesetValue_ArtifactLink */
    private $previous_changesetvalue;

    /** @var Artifact */
    private $artifact;

    /** @var Artifact */
    private $art_123;

    /** @var Artifact */
    private $art_124;
    /**
     * @var Artifact&Mockery\MockInterface
     */
    private $art_201;

    protected function setUp(): void
    {
        $tracker = Mockery::spy(\Tracker::class);

        $changesets_123 = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changesets_123->shouldReceive('getId')->andReturns(1231);
        $changesets_124 = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changesets_124->shouldReceive('getId')->andReturns(1241);
        $changesets_201 = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changesets_201->shouldReceive('getId')->andReturns(2011);

        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(120);
        $this->art_123 = Mockery::mock(Artifact::class);
        $this->art_123->shouldReceive('getId')->andReturn(123);
        $this->art_123->shouldReceive('getTracker')->andReturn($tracker);
        $this->art_123->shouldReceive('getLastChangeset')->andReturn($changesets_123);
        $this->art_124 = Mockery::mock(Artifact::class);
        $this->art_124->shouldReceive('getId')->andReturn(124);
        $this->art_124->shouldReceive('getTracker')->andReturn($tracker);
        $this->art_124->shouldReceive('getLastChangeset')->andReturn($changesets_124);
        $this->art_201 = Mockery::mock(Artifact::class);
        $this->art_201->shouldReceive('getId')->andReturn(201);
        $this->art_201->shouldReceive('getTracker')->andReturn($tracker);
        $this->art_201->shouldReceive('getLastChangeset')->andReturn($changesets_201);

        $artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $this->previous_changesetvalue = \Mockery::spy(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $this->previous_changesetvalue->shouldReceive('getValue')->andReturns(
            [
                201 => Tracker_ArtifactLinkInfo::buildFromArtifact($this->art_201, '_is_child'),
            ]
        );

        $artifact_factory->shouldReceive('getArtifactById')->with(123)->andReturns($this->art_123);
        $artifact_factory->shouldReceive('getArtifactById')->with(124)->andReturns($this->art_124);
        $artifact_factory->shouldReceive('getArtifactById')->with(201)->andReturns($this->art_201);

        $this->convertor = new SubmittedValueConvertor(
            $artifact_factory,
        );
    }

    public function testItChangesTheTypeOfAnExistingLink(): void
    {
        $submitted_value = [
            'new_values' => '',
            'types'      => [
                '201' => 'fixed_in',
            ],
        ];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );

        $this->assertEquals('fixed_in', $updated_submitted_value['list_of_artifactlinkinfo'][201]->getType());
    }

    public function testItChangesTheTypeToNullOfAnExistingLink(): void
    {
        $submitted_value = [
            'new_values' => '',
            'types'      => [
                '201' => '',
            ],
        ];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );

        $this->assertEquals(null, $updated_submitted_value['list_of_artifactlinkinfo'][201]->getType());
    }

    public function testItDoesNotMutateTheExistingArtifactLinkInfo(): void
    {
        $submitted_value = [
            'new_values' => '',
            'types'      => [
                '201' => '_is_child',
            ],
        ];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );

        $existing_list_of_artifactlinkinfo = $this->previous_changesetvalue->getValue();

        $this->assertEquals(
            $existing_list_of_artifactlinkinfo[201],
            $updated_submitted_value['list_of_artifactlinkinfo'][201]
        );
    }

    public function testItConvertsWhenThereIsNoType(): void
    {
        $submitted_value = ['new_values' => '123, 124'];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );
        $this->assertEquals(null, $updated_submitted_value['list_of_artifactlinkinfo']['123']->getType());
        $this->assertEquals(null, $updated_submitted_value['list_of_artifactlinkinfo']['124']->getType());
    }

    public function testItConvertsWhenThereIsOnlyOneType(): void
    {
        $submitted_value = [
            'new_values' => '123, 124',
            'types'      => ['123' => '_is_child', '124' => '_is_child'],
        ];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );
        $this->assertEquals('_is_child', $updated_submitted_value['list_of_artifactlinkinfo']['123']->getType());
        $this->assertEquals('_is_child', $updated_submitted_value['list_of_artifactlinkinfo']['124']->getType());
    }

    public function testItConvertsWhenEachArtifactLinkHasItsOwnType(): void
    {
        $submitted_value = [
            'new_values' => '123, 124',
            'types'      => ['123' => '_is_child', '124' => '_is_foo'],
        ];

        $updated_submitted_value = $this->convertor->convert(
            $submitted_value,
            $this->previous_changesetvalue
        );
        $this->assertEquals('_is_child', $updated_submitted_value['list_of_artifactlinkinfo']['123']->getType());
        $this->assertEquals('_is_foo', $updated_submitted_value['list_of_artifactlinkinfo']['124']->getType());
    }
}
