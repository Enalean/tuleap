<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *  This file is a part of Tuleap.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueEmptyChecker;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;

class Tracker_FormElement_Field_ArtifactLinkTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;

    protected function setUp(): void
    {
        $this->changeset = Mockery::spy(Tracker_Artifact_Changeset::class);
        $this->changeset->shouldReceive('getArtifact')->andReturn(Mockery::spy(Artifact::class));
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function testNoDefaultValue(): void
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getProperty')->andReturn(null);
        $this->assertFalse($field->hasDefaultValue());
    }

    public function testGetChangesetValue(): void
    {
        $value_dao = Mockery::mock(
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao::class
        );
        $value_dao->shouldReceive('searchById')->andReturn(TestHelper::arrayToDar([
            'id' => 123,
            'field_id' => 1,
            'artifact_id' => '999',
            'keyword' => 'bug',
            'group_id' => '102',
            'tracker_id' => '456',
            'nature' => '',
            'last_changeset_id' => '789',
        ]));
        $value_dao->shouldReceive('searchReverseLinksById')->andReturn(TestHelper::emptyDar());

        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getValueDao')->andReturns($value_dao);

        $this->assertInstanceOf(Tracker_Artifact_ChangesetValue_ArtifactLink::class, $field->getChangesetValue($this->changeset, 123, false));
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $value_dao = Mockery::mock(
            \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao::class
        );
        $value_dao->shouldReceive('searchById')->andReturn(TestHelper::emptyDar());
        $value_dao->shouldReceive('searchReverseLinksById')->andReturn(TestHelper::emptyDar());

        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getValueDao')->andReturns($value_dao);

        $this->assertNotNull($field->getChangesetValue($this->changeset, 123, false));
    }

    public function testFetchRawValue(): void
    {
        $f       = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $art_ids = ['123, 132, 999'];
        $value   = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $value->shouldReceive('getArtifactIds')->andReturns($art_ids);
        $this->assertEquals('123, 132, 999', $f->fetchRawValue($value));
    }

    public function testIsValidRequiredFieldWithExistingValues(): void
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('isRequired')->andReturns(true);

        $ids = [123];
        $cv  = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $cv->shouldReceive('getArtifactIds')->andReturns($ids);
        $c = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $c->shouldReceive('getValue')->andReturns($cv);

        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker  = Mockery::mock(\Tracker::class);
        $artifact->shouldReceive('getLastChangeset')->andReturn($c);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getId')->andReturn(1);

        $field->shouldReceive('getLastChangesetValue')->andReturns($cv);
        $field->shouldReceive('getReverseLinks')->andReturns([]);

        $this->assertTrue($field->isValidRegardingRequiredProperty($artifact, null));  // existing values
        $this->assertFalse($field->isValidRegardingRequiredProperty($artifact, ['new_values' => '', 'removed_values' => ['123']]));
    }

    public function testIsValidRequiredFieldWithNullValue(): void
    {
        $field = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('isRequired')->andReturns(true);

        $ids = [];
        $cv  = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $cv->shouldReceive('getArtifactIds')->andReturns($ids);
        $c = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $c->shouldReceive('getValue')->andReturns($cv);
        $a = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $a->shouldReceive('getLastChangeset')->andReturns($c);

        $checker = $this->createMock(SubmittedValueEmptyChecker::class);
        $checker->method('isSubmittedValueEmpty')->willReturn(true);
        $field->shouldReceive('getSubmittedValueEmptyChecker')->andReturns($checker);

        $this->assertFalse($field->isValidRegardingRequiredProperty($a, null));
    }

    public function testIsValidAddsErrorIfARequiredFieldValueIsEmpty(): void
    {
        $f = Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('isRequired')->andReturns(true);

        $checker = $this->createMock(SubmittedValueEmptyChecker::class);
        $checker->method('isSubmittedValueEmpty')->willReturn(true);
        $f->shouldReceive('getSubmittedValueEmptyChecker')->andReturns($checker);

        $a = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $a->shouldReceive('getLastChangeset')->andReturns(false);
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, []));
        $this->assertTrue($f->hasErrors());
    }

    public function testReturnsAnEmptyListWhenThereAreNoValuesInTheChangeset(): void
    {
        $field     = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $changeset = Mockery::spy(\Tracker_Artifact_Changeset::class);
        $user      = Mockery::mock(PFUser::class);

        $artifacts = $field->getLinkedArtifacts($changeset, $user);
        $this->assertEmpty($artifacts);
    }

    public function testReturnsAnEmptyPaginatedListWhenThereAreNoValuesInTheChangeset(): void
    {
        $field     = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $changeset = Mockery::spy(\Tracker_Artifact_Changeset::class);
        $user      = Mockery::mock(PFUser::class);

        $sliced = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $this->assertEmpty($sliced->getArtifacts());
        $this->assertEquals(0, $sliced->getTotalSize());
    }

    public function testCreatesAListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->andReturn(true);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->andReturn(true);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = [
            $artifact_1,
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $artifacts);
    }

    public function testCreatesAPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->andReturn(true);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->andReturn(true);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = [
            $artifact_1,
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testCreatesAFirstPageOfPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->andReturn(true);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->andReturn(true);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 0);
        $expected_artifacts = [
            $artifact_1,
        ];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testCreatesASecondPageOfPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->andReturn(true);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->andReturn(true);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 1);
        $expected_artifacts = [
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testIgnoresIdsThatDontExist(): void
    {
        $user     = Mockery::mock(PFUser::class);
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(123);
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact]));

        $non_existing_id = 666;
        $changeset       = $this->givenAChangesetValueWithArtifactIds($field, [123, $non_existing_id]);

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = [$artifact];
        $this->assertEquals($expected_artifacts, $artifacts);
    }

    public function testIgnoresInPaginatedListIdsThatDontExist(): void
    {
        $user     = Mockery::mock(PFUser::class);
        $artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(123);
        $artifact->shouldReceive('userCanView')->andReturn(true);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact]));

        $non_existing_id = 666;
        $changeset       = $this->givenAChangesetValueWithArtifactIds($field, [123, $non_existing_id]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = [$artifact];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testReturnsOnlyArtifactsAccessibleByGivenUser(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->with($user)->andReturn(false);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->with($user)->andReturn(true);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = [
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $artifacts);
    }

    public function testReturnsOnlyPaginatedArtifactsAccessibleByGivenUser(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->with($user)->andReturn(false);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->with($user)->andReturn(true);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = [
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testReturnsAFirstPageOfOnlyPaginatedArtifactsAccessibleByGivenUser(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->with($user)->andReturn(false);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->with($user)->andReturn(true);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 0);
        $expected_artifacts = [];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testReturnsASecondPageOfOnlyPaginatedArtifactsAccessibleByGivenUser(): void
    {
        $user = Mockery::mock(PFUser::class);

        $artifact_1 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_1->shouldReceive('getId')->andReturn(123);
        $artifact_1->shouldReceive('userCanView')->with($user)->andReturn(false);
        $artifact_2 = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact_2->shouldReceive('getId')->andReturn(345);
        $artifact_2->shouldReceive('userCanView')->with($user)->andReturn(true);

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 1);
        $expected_artifacts = [
            $artifact_2,
        ];
        $this->assertEquals($expected_artifacts, $sliced->getArtifacts());
        $this->assertEquals(2, $sliced->getTotalSize());
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();

        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $value = 'some_value';

        $field->getFieldDataFromRESTValueByField($value);
    }

    public function testItDoesNotRaiseWarningIfItDoesNotHaveAllInformationToDisplayAnAsyncRenderer(): void
    {
        $this->expectNotToPerformAssertions();
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();

        $field->process(
            $this->createMock(Tracker_IDisplayTrackerLayout::class),
            new class extends Codendi_Request {
                public function __construct()
                {
                    parent::__construct([]);
                }

                public function get($variable)
                {
                    return [
                        'func' => 'artifactlink-renderer-async',
                        'renderer_data' => json_encode(["artifact_id" => 123]),
                    ][$variable];
                }

                public function isAjax()
                {
                    return true;
                }
            },
            \Tuleap\Test\Builders\UserTestBuilder::anAnonymousUser()->build()
        );
    }

    private function givenAChangesetValueWithArtifactIds(Tracker_FormElement_Field_ArtifactLink $field, array $ids): Tracker_Artifact_Changeset
    {
        $changeset_value = Mockery::spy(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset_value->shouldReceive('getArtifactIds')->andReturns($ids);
        $changeset = Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValue')->with($field)->andReturns($changeset_value);
        return $changeset;
    }

    private function givenAnArtifactFactory(array $artifacts): Tracker_ArtifactFactory
    {
        $factory = Mockery::spy(\Tracker_ArtifactFactory::class);
        foreach ($artifacts as $a) {
            $factory->shouldReceive('getArtifactById')->with($a->getId())->andReturns($a);
        }
        return $factory;
    }
}
