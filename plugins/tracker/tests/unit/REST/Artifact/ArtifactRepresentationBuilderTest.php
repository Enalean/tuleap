<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

require_once __DIR__ . '/../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Changeset;
use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\REST\ChangesetRepresentation;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\TrackerColor;

final class ArtifactRepresentationBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var ArtifactRepresentationBuilder */
    private $builder;
    /** @var Mockery\MockInterface */
    private $form_element_factory;
    /** @var Mockery\MockInterface */
    private $artifact_factory;
    /** @var Mockery\MockInterface */
    private $nature_dao;

    public function setUp(): void
    {
        $this->form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->artifact_factory     = Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->nature_dao           = Mockery::mock(NatureDao::class);
        $this->builder              = new ArtifactRepresentationBuilder(
            $this->form_element_factory,
            $this->artifact_factory,
            $this->nature_dao
        );
    }

    public function testGetArtifactRepresentationReturnsArtifactRepresentationWithoutFields()
    {
        $current_user = Mockery::mock(\PFUser::class);
        $current_user->shouldReceive(
            [
                'getId'        => 153,
                'getRealName'  => 'Ophelia Reckleben',
                'getUserName'  => 'oreckleben',
                'getName'      => 'Ophelia Reckleben',
                'getLdapId'    => 10410,
                'getAvatarUrl' => '',
                'isAnonymous'  => false,
                'isNone'       => false,
                'hasAvatar'    => false
            ]
        );

        $tracker  = $this->buildTrackerMock();
        $artifact = Mockery::spy(\Tracker_Artifact::class);
        $artifact->shouldReceive(
            [
                'getId'              => 12,
                'getTracker'         => $tracker,
                'getSubmittedBy'     => 777,
                'getSubmittedOn'     => 6546546554,
                'getLastChangeset'   => Mockery::mock(\Tracker_Artifact_Changeset::class),
                'getSubmittedByUser' => Mockery::spy(\PFUser::class),
                'getUri'             => '/plugins/tracker/?aid=12',
                'getXRef'            => 'Tracker_Artifact #12',
                'getAssignedTo'      => [$current_user]
            ]
        );

        $representation = $this->builder->getArtifactRepresentation($current_user, $artifact);

        $this->assertEquals(12, $representation->id);
        $this->assertEquals(
            ArtifactRepresentation::ROUTE . '/' . 12,
            $representation->uri
        );
        $this->assertEquals(888, $representation->tracker->id);
        $this->assertEquals(
            CompleteTrackerRepresentation::ROUTE . '/' . 888,
            $representation->tracker->uri
        );
        $this->assertEquals(1478, $representation->project->id);
        $this->assertEquals('projects/1478', $representation->project->uri);
        $this->assertEquals(777, $representation->submitted_by);
        $this->assertEquals('2177-06-14T06:09:14+01:00', $representation->submitted_on);
        $this->assertEquals('/plugins/tracker/?aid=12', $representation->html_url);
        $this->assertEquals(
            ArtifactRepresentation::ROUTE . '/' . 12 . '/' . ChangesetRepresentation::ROUTE,
            $representation->changesets_uri
        );
        $this->assertEquals('Tracker_Artifact #12', $representation->xref);
        $this->assertEquals(153, $representation->assignees[0]->id);
        $this->assertEquals('oreckleben', $representation->assignees[0]->username);
    }

    public function testGetArtifactRepresentationWithFieldValuesWhenThereAreNoFields()
    {
        $current_user = Mockery::mock(\PFUser::class);
        $artifact     = $this->buildBasicArtifactMock();
        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')->andReturn([])->once();

        $this->builder->getArtifactRepresentationWithFieldValues($current_user, $artifact, new MinimalTrackerRepresentation());
    }

    public function testGetArtifactRepresentationWithFieldValuesDoesntIncludeFieldsUserCantRead()
    {
        $current_user = Mockery::mock(\PFUser::class);

        $first_field_user_cant_read = Mockery::mock(\Tracker_FormElement_Field::class);
        $first_field_user_cant_read
            ->shouldReceive('userCanRead')
            ->with($current_user)
            ->andReturnFalse();
        $first_field_user_cant_read->shouldNotReceive('getRESTValue');
        $field_user_can_read = Mockery::mock(\Tracker_FormElement_Field::class);
        $field_user_can_read
            ->shouldReceive('userCanRead')
            ->with($current_user)
            ->andReturnTrue();
        $field_user_can_read->shouldReceive('getRESTValue')->once();
        $second_field_user_cant_read = Mockery::mock(\Tracker_FormElement_Field::class);
        $second_field_user_cant_read
            ->shouldReceive('userCanRead')
            ->with($current_user)
            ->andReturnFalse();
        $second_field_user_cant_read->shouldNotReceive('getRESTValue');

        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')->andReturn(
            [
                $first_field_user_cant_read,
                $field_user_can_read,
                $second_field_user_cant_read
            ]
        );
        $artifact = $this->buildBasicArtifactMock();

        $this->builder->getArtifactRepresentationWithFieldValues($current_user, $artifact, new MinimalTrackerRepresentation());
    }

    public function testGetArtifactRepresentationWithFieldValuesReturnsOnlyForFieldsWithValues()
    {
        $first_field  = Mockery::mock(\Tracker_FormElement_Field::class)
            ->shouldReceive('userCanRead')
            ->andReturnFalse()
            ->getMock();
        $second_field = Mockery::mock(\Tracker_FormElement_Field::class);
        $second_field->shouldReceive(
            [
                'userCanRead'  => true,
                'getRESTValue' => 'whatever'
            ]
        );
        $third_field = Mockery::mock(\Tracker_FormElement_Field::class)
            ->shouldReceive('userCanRead')
            ->andReturnFalse()
            ->getMock();
        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')->andReturn(
            [
                $first_field,
                $second_field,
                $third_field
            ]
        );

        $current_user = Mockery::mock(\PFUser::class);
        $artifact     = $this->buildBasicArtifactMock();

        $representation = $this->builder->getArtifactRepresentationWithFieldValues($current_user, $artifact, new MinimalTrackerRepresentation());

        $this->assertEquals(['whatever'], $representation->values);
        $this->assertNull($representation->values_by_field);
    }

    public function testGetArtifactRepresentationWithFieldValuesByFieldValuesReturnsSimpleValues()
    {
        $first_field = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $first_field->shouldReceive(
            [
                'userCanRead'  => true,
                'getName'      => 'field01',
                'getRESTValue' => '01'
            ]
        );
        $second_field = Mockery::mock(\Tracker_FormElement_Field_String::class);
        $second_field->shouldReceive(
            [
                'userCanRead'  => true,
                'getName'      => 'field02',
                'getRESTValue' => 'whatever'
            ]
        );
        $third_field = Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $third_field->shouldReceive('userCanRead')->andReturnFalse();
        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')->andReturn(
            [$first_field, $second_field, $third_field]
        );

        $current_user = Mockery::mock(\PFUser::class);
        $artifact     = $this->buildBasicArtifactMock();

        $representation = $this->builder->getArtifactRepresentationWithFieldValuesByFieldValues(
            $current_user,
            $artifact,
            new MinimalTrackerRepresentation()
        );

        $this->assertNull($representation->values);
        $this->assertEquals(['field01' => '01', 'field02' => 'whatever'], $representation->values_by_field);
    }

    public function testGetArtifactRepresentationWithFieldValuesInBothFormat()
    {
        $first_field = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $first_field->shouldReceive(
            [
                'userCanRead'  => true,
                'getName'      => 'field01',
                'getRESTValue' => '01'
            ]
        );
        $second_field = Mockery::mock(\Tracker_FormElement_Field_String::class);
        $second_field->shouldReceive(
            [
                'userCanRead'  => true,
                'getName'      => 'field02',
                'getRESTValue' => 'whatever'
            ]
        );
        $third_field = Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $third_field->shouldReceive('userCanRead')->andReturnFalse();
        $this->form_element_factory->shouldReceive('getUsedFieldsForREST')->andReturn(
            [$first_field, $second_field, $third_field]
        );

        $current_user = Mockery::mock(\PFUser::class);
        $artifact     = $this->buildBasicArtifactMock();

        $representation = $this->builder->getArtifactRepresentationWithFieldValuesInBothFormat(
            $current_user,
            $artifact,
            new MinimalTrackerRepresentation()
        );

        $this->assertEquals(['01', 'whatever'], $representation->values);
        $this->assertEquals(['field01' => '01', 'field02' => 'whatever'], $representation->values_by_field);
    }

    public function testGetArtifactChangesetsRepresentationReturnsEmptyArrayWhenNoChanges()
    {
        $current_user = Mockery::mock(\PFUser::class);
        $artifact     = $this->buildBasicArtifactMock();
        $artifact->shouldReceive('getChangesets')->andReturn([]);

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            false
        );

        $this->assertSame([], $representation->toArray());
    }

    public function testGetArtifactChangesetsRepresentationBuildsHistoryOutOfChangeset()
    {
        $current_user = Mockery::mock(\PFUser::class);
        $changeset1   = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset1->shouldReceive('getRESTValue')->with($current_user, Tracker_Artifact_Changeset::FIELDS_ALL)->once();
        $artifact = $this->buildBasicArtifactMock();
        $artifact->shouldReceive('getChangesets')->andReturn([$changeset1]);

        $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            false
        )->toArray();
    }

    public function testGetArtifactChangesetsRepresentationDoesntExportEmptyChanges()
    {
        $changeset1 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset2 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset1->shouldReceive('getRESTValue')->andReturnNull();
        $changeset2->shouldReceive('getRESTValue')->andReturn('whatever');

        $artifact = $this->buildBasicArtifactMock();
        $artifact->shouldReceive('getChangesets')->andReturn([$changeset1, $changeset2]);
        $current_user = Mockery::mock(\PFUser::class);

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            false
        );

        $this->assertEquals(['whatever'], $representation->toArray());
    }

    public function testGetArtifactChangesetsRepresentationPaginatesResults()
    {
        $changeset1 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset2 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset1->shouldReceive('getRESTValue')->andReturn('result 1');
        $changeset2->shouldReceive('getRESTValue')->andReturn('result 2');

        $artifact = $this->buildBasicArtifactMock();
        $artifact->shouldReceive('getChangesets')->andReturn([$changeset1, $changeset2]);
        $current_user = Mockery::mock(\PFUser::class);

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            1,
            10,
            false
        );

        $this->assertEquals(['result 2'], $representation->toArray());
    }

    public function testGetArtifactChangesetsRepresentationReturnsTheTotalCountOfResults()
    {
        $changeset1 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset2 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset1->shouldReceive('getRESTValue')->andReturn('result 1');
        $changeset2->shouldReceive('getRESTValue')->andReturn('result 2');

        $artifact = $this->buildBasicArtifactMock();
        $artifact->shouldReceive('getChangesets')->andReturn([$changeset1, $changeset2]);
        $current_user = Mockery::mock(\PFUser::class);

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            1,
            10,
            false
        );

        $this->assertSame(2, $representation->totalCount());
    }

    public function testGetArtifactChangesetsRepresentationReturnsTheChangesetsInReverseOrder()
    {
        $changeset1 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset2 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset1->shouldReceive('getRESTValue')->andReturn('result 1');
        $changeset2->shouldReceive('getRESTValue')->andReturn('result 2');

        $artifact = $this->buildBasicArtifactMock();
        $artifact->shouldReceive('getChangesets')->andReturn([$changeset1, $changeset2]);
        $current_user = Mockery::mock(\PFUser::class);

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            true
        );

        $this->assertEquals(['result 2', 'result 1'], $representation->toArray());
    }

    /**
     * @return Mockery\MockInterface|\Tracker
     */
    private function buildTrackerMock()
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive(
            [
                'getID'                    => 1478,
                'getPublicName' => 'Gliddery Argulus'
            ]
        );
        $project->shouldReceive('getID')->andReturn(1478);

        $tracker = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive(
            [
                'getId'      => 888,
                'getProject' => $project,
                'getName'    => 'Tracker_Artifact',
                'getColor'   => TrackerColor::default()
            ]
        );
        return $tracker;
    }

    /**
     * @return Mockery\MockInterface|\Tracker_Artifact
     */
    private function buildBasicArtifactMock()
    {
        $tracker  = $this->buildTrackerMock();
        $artifact = Mockery::spy(\Tracker_Artifact::class);
        $artifact->shouldReceive(
            [
                'getTracker'         => $tracker,
                'getLastChangeset'   => Mockery::mock(\Tracker_Artifact_Changeset::class),
                'getSubmittedByUser' => Mockery::spy(\PFUser::class),
                'getAssignedTo'      => []
            ]
        );
        return $artifact;
    }
}
