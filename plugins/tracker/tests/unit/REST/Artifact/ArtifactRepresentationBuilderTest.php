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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_Changeset;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\NatureDao;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentation;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\Changeset\Comment\HTMLOrTextCommentRepresentation;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\TrackerColor;

final class ArtifactRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var ArtifactRepresentationBuilder */
    private $builder;
    /** @var Mockery\MockInterface */
    private $form_element_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ChangesetRepresentationBuilder
     */
    private $changeset_representation_builder;

    public function setUp(): void
    {
        $this->form_element_factory             = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->changeset_representation_builder = Mockery::mock(ChangesetRepresentationBuilder::class);
        $this->builder                          = new ArtifactRepresentationBuilder(
            $this->form_element_factory,
            Mockery::mock(\Tracker_ArtifactFactory::class),
            Mockery::mock(NatureDao::class),
            $this->changeset_representation_builder
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

        $tracker      = $this->buildTrackerMock();
        $artifact     = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $submitted_by = UserTestBuilder::aUser()->withId(777)->build();
        $artifact->shouldReceive(
            [
                'getId'              => 12,
                'getTracker'         => $tracker,
                'getSubmittedBy'     => 777,
                'getSubmittedOn'     => 6546546554,
                'getLastUpdateDate'  => 6546546554,
                'getLastChangeset'   => Mockery::mock(\Tracker_Artifact_Changeset::class),
                'getSubmittedByUser' => $submitted_by,
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

        $this->builder->getArtifactRepresentationWithFieldValues($current_user, $artifact, self::buildMinimalTrackerRepresentation());
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

        $this->builder->getArtifactRepresentationWithFieldValues($current_user, $artifact, self::buildMinimalTrackerRepresentation());
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

        $representation = $this->builder->getArtifactRepresentationWithFieldValues($current_user, $artifact, self::buildMinimalTrackerRepresentation());

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
            self::buildMinimalTrackerRepresentation()
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
            self::buildMinimalTrackerRepresentation()
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

    public function testGetArtifactChangesetsRepresentationBuildsHistoryOutOfChangeset(): void
    {
        $current_user = Mockery::mock(\PFUser::class);
        $changeset1   = Mockery::mock(Tracker_Artifact_Changeset::class);
        $artifact     = $this->buildBasicArtifactMock();
        $artifact->shouldReceive('getChangesets')->andReturn([$changeset1]);

        $this->changeset_representation_builder->shouldReceive('buildWithFields')
            ->once()
            ->with($changeset1, \Tracker_Artifact_Changeset::FIELDS_ALL, $current_user, null)
            ->andReturn($this->buildChangesetRepresentation());

        $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            false
        )->toArray();
    }

    public function testGetArtifactChangesetsRepresentationDoesntExportEmptyChanges(): void
    {
        $current_user = Mockery::mock(\PFUser::class);
        $changeset1   = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset2   = Mockery::mock(Tracker_Artifact_Changeset::class);

        $artifact = $this->buildBasicArtifactMock();
        $artifact->shouldReceive('getChangesets')->andReturn([$changeset1, $changeset2]);

        $changeset_representation1 = $this->buildChangesetRepresentation();
        $this->changeset_representation_builder->shouldReceive('buildWithFields')
            ->once()
            ->with($changeset1, \Tracker_Artifact_Changeset::FIELDS_ALL, $current_user, null)
            ->andReturn($changeset_representation1);
        $this->changeset_representation_builder->shouldReceive('buildWithFields')
            ->once()
            ->with($changeset2, \Tracker_Artifact_Changeset::FIELDS_ALL, $current_user, $changeset1)
            ->andReturnNull();

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            false
        );

        self::assertEquals([$changeset_representation1], $representation->toArray());
    }

    public function testGetArtifactChangesetsRepresentationPaginatesResults(): void
    {
        $changeset1 = $this->buildChangeset(1001);
        $changeset2 = $this->buildChangeset(1002);

        $artifact = $this->buildBasicArtifactMock();
        $artifact->shouldReceive('getChangesets')->andReturn([$changeset1, $changeset2]);
        $current_user = Mockery::mock(\PFUser::class);

        $first_representation  = $this->buildChangesetRepresentation(1001);
        $second_representation = $this->buildChangesetRepresentation(1002);
        $this->changeset_representation_builder->shouldReceive('buildWithFields')
            ->andReturnUsing(
                function (\Tracker_Artifact_Changeset $changeset, string $mode, \PFUser $user) use ($first_representation, $second_representation) {
                    return ($changeset->getId() === 1001) ? $first_representation : $second_representation;
                }
            );

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            1,
            10,
            false
        );

        self::assertEquals([$second_representation], $representation->toArray());
        self::assertSame(2, $representation->totalCount());
    }

    public function testGetArtifactChangesetsRepresentationReturnsTheChangesetsInReverseOrder(): void
    {
        $changeset1 = $this->buildChangeset(1001);
        $changeset2 = $this->buildChangeset(1002);

        $artifact = $this->buildBasicArtifactMock();
        $artifact->shouldReceive('getChangesets')->andReturn([$changeset1, $changeset2]);
        $current_user = Mockery::mock(\PFUser::class);

        $first_representation  = $this->buildChangesetRepresentation(1001);
        $second_representation = $this->buildChangesetRepresentation(1002);
        $this->changeset_representation_builder->shouldReceive('buildWithFields')
            ->andReturnUsing(
                function (\Tracker_Artifact_Changeset $changeset, string $mode, \PFUser $user) use ($first_representation, $second_representation) {
                    return ($changeset->getId() === 1001) ? $first_representation : $second_representation;
                }
            );

        $representation = $this->builder->getArtifactChangesetsRepresentation(
            $current_user,
            $artifact,
            Tracker_Artifact_Changeset::FIELDS_ALL,
            0,
            10,
            true
        );

        self::assertEquals([$second_representation, $first_representation], $representation->toArray());
    }

    /**
     * @return Mockery\MockInterface|\Tracker
     */
    private function buildTrackerMock()
    {
        $project = ProjectTestBuilder::aProject()->withId(1478)->build();

        $tracker = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive(
            [
                'getId'      => 888,
                'getProject' => $project,
                'getName'    => 'Tuleap\Artifact\Artifact',
                'getColor'   => TrackerColor::default()
            ]
        );
        return $tracker;
    }

    /**
     * @return Mockery\MockInterface|\Tuleap\Tracker\Artifact\Artifact
     */
    private function buildBasicArtifactMock()
    {
        $tracker  = $this->buildTrackerMock();
        $artifact = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive(
            [
                'getId'              => 756,
                'getTracker'         => $tracker,
                'getLastChangeset'   => Mockery::mock(\Tracker_Artifact_Changeset::class),
                'getSubmittedByUser' => UserTestBuilder::aUser()->build(),
                'getAssignedTo'      => [],
                'getXRef'            => 'art #756',
                'getSubmittedBy'     => 111,
                'getSubmittedOn'     => 10,
                'getUri'             => '/uri/artifact/756',
                'getLastUpdateDate'  => 10,
            ]
        );
        return $artifact;
    }

    private static function buildMinimalTrackerRepresentation(): MinimalTrackerRepresentation
    {
        $tracker = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(859);
        $tracker->shouldReceive('getName')->andReturn('tracker name');
        $tracker->shouldReceive('getColor')->andReturn(TrackerColor::default());
        $tracker->shouldReceive('getProject')->andReturn(Mockery::spy(\Project::class));

        return MinimalTrackerRepresentation::build($tracker);
    }

    private function buildChangesetRepresentation(int $changeset_id = 1001): ChangesetRepresentation
    {
        $comment_representation = new HTMLOrTextCommentRepresentation('Irrelevant', 'Irrelevant', 'text', null);
        return new ChangesetRepresentation(
            $changeset_id,
            110,
            null,
            1234567890,
            null,
            $comment_representation,
            [],
            null,
            1234567890
        );
    }

    private function buildChangeset(int $changeset_id = 1001): \Tracker_Artifact_Changeset
    {
        $changeset = new \Tracker_Artifact_Changeset(
            $changeset_id,
            \Mockery::mock(Artifact::class),
            110,
            1234567890,
            null
        );
        $comment   = new \Tracker_Artifact_Changeset_Comment(
            201,
            $changeset,
            null,
            null,
            110,
            1234567890,
            'A text comment',
            'text',
            0,
            []
        );
        $changeset->setLatestComment($comment);
        return $changeset;
    }
}
