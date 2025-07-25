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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use TestHelper;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use Tracker_IDisplayTrackerLayout;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\ArtifactLink\ArtifactLinkChangesetValue;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueEmptyChecker;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkFieldTest extends TestCase
{
    use GlobalResponseMock;

    private Tracker_Artifact_Changeset $changeset;

    #[\Override]
    protected function setUp(): void
    {
        $this->changeset = ChangesetTestBuilder::aChangeset(624)->ofArtifact(ArtifactTestBuilder::anArtifact(623)->build())->build();
    }

    #[\Override]
    protected function tearDown(): void
    {
        UserManager::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function testNoDefaultValue(): void
    {
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(12)->withSpecificProperty('default_value', ['value' => null])->build();
        self::assertFalse($field->hasDefaultValue());
    }

    public function testGetChangesetValue(): void
    {
        $value_dao = $this->createMock(ArtifactLinkFieldValueDao::class);
        $value_dao->method('searchById')->willReturn(TestHelper::arrayToDar([
            'id'                => 123,
            'field_id'          => 1,
            'artifact_id'       => '999',
            'keyword'           => 'bug',
            'group_id'          => '102',
            'tracker_id'        => '456',
            'nature'            => '',
            'last_changeset_id' => '789',
        ]));
        $value_dao->method('searchReverseLinksById')->willReturn(TestHelper::emptyDar());

        $field = $this->createPartialMock(ArtifactLinkField::class, ['getValueDao']);
        $field->method('getValueDao')->willReturn($value_dao);

        self::assertInstanceOf(ArtifactLinkChangesetValue::class, $field->getChangesetValue($this->changeset, 123, false));
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $value_dao = $this->createMock(ArtifactLinkFieldValueDao::class);
        $value_dao->method('searchById')->willReturn(TestHelper::emptyDar());
        $value_dao->method('searchReverseLinksById')->willReturn(TestHelper::emptyDar());

        $field = $this->createPartialMock(ArtifactLinkField::class, ['getValueDao']);
        $field->method('getValueDao')->willReturn($value_dao);

        self::assertNotNull($field->getChangesetValue($this->changeset, 123, false));
    }

    public function testFetchRawValue(): void
    {
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(651)->build();
        $value = ChangesetValueArtifactLinkTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(1)->build(), $field)
            ->withForwardLinks([
                123 => Tracker_ArtifactLinkInfo::buildFromArtifact(
                    ArtifactTestBuilder::anArtifact(123)->withChangesets(ChangesetTestBuilder::aChangeset(1235)->build())->build(),
                    '',
                ),
                132 => Tracker_ArtifactLinkInfo::buildFromArtifact(
                    ArtifactTestBuilder::anArtifact(132)->withChangesets(ChangesetTestBuilder::aChangeset(1325)->build())->build(),
                    '',
                ),
                999 => Tracker_ArtifactLinkInfo::buildFromArtifact(
                    ArtifactTestBuilder::anArtifact(999)->withChangesets(ChangesetTestBuilder::aChangeset(9995)->build())->build(),
                    '',
                ),
            ])
            ->build();
        self::assertEquals('123, 132, 999', $field->fetchRawValue($value));
    }

    public function testIsValidRequiredFieldWithExistingValues(): void
    {
        $field = $this->createPartialMock(ArtifactLinkField::class, ['isRequired', 'getLastChangesetValue', 'getReverseLinks']);
        $field->method('isRequired')->willReturn(true);

        $changeset = ChangesetTestBuilder::aChangeset(6512)->build();
        $value     = ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset, $field)
            ->withForwardLinks([
                123 => Tracker_ArtifactLinkInfo::buildFromArtifact(
                    ArtifactTestBuilder::anArtifact(123)->withChangesets(ChangesetTestBuilder::aChangeset(1235)->build())->build(),
                    '',
                ),
            ])
            ->build();
        $changeset->setFieldValue($field, $value);

        $artifact = ArtifactTestBuilder::anArtifact(1)->withChangesets($changeset)->build();

        $field->method('getLastChangesetValue')->willReturn($value);
        $field->method('getReverseLinks')->willReturn([]);

        self::assertTrue($field->isValidRegardingRequiredProperty($artifact, null));  // existing values
        self::assertFalse($field->isValidRegardingRequiredProperty($artifact, ['new_values' => '', 'removed_values' => ['123']]));
    }

    public function testIsValidRequiredFieldWithNullValue(): void
    {
        $field = $this->createPartialMock(ArtifactLinkField::class, ['isRequired', 'getSubmittedValueEmptyChecker']);
        $field->method('isRequired')->willReturn(true);

        $changeset = ChangesetTestBuilder::aChangeset(654)->build();
        $value     = ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset, $field)->build();
        $changeset->setFieldValue($field, $value);
        $artifact = ArtifactTestBuilder::anArtifact(521)->withChangesets($changeset)->build();

        $checker = $this->createMock(SubmittedValueEmptyChecker::class);
        $checker->method('isSubmittedValueEmpty')->willReturn(true);
        $field->method('getSubmittedValueEmptyChecker')->willReturn($checker);

        self::assertFalse($field->isValidRegardingRequiredProperty($artifact, null));
    }

    public function testIsValidAddsErrorIfARequiredFieldValueIsEmpty(): void
    {
        $field = $this->createPartialMock(ArtifactLinkField::class, ['isRequired', 'getSubmittedValueEmptyChecker']);
        $field->method('isRequired')->willReturn(true);

        $checker = $this->createMock(SubmittedValueEmptyChecker::class);
        $checker->method('isSubmittedValueEmpty')->willReturn(true);
        $field->method('getSubmittedValueEmptyChecker')->willReturn($checker);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getLastChangeset')->willReturn(null);
        self::assertFalse($field->isValidRegardingRequiredProperty($artifact, []));
        self::assertTrue($field->hasErrors());
    }

    public function testReturnsAnEmptyListWhenThereAreNoValuesInTheChangeset(): void
    {
        $field     = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $user      = UserTestBuilder::buildWithDefaults();

        $changeset->setFieldValue($field, null);

        $artifacts = $field->getLinkedArtifacts($changeset, $user);
        self::assertEmpty($artifacts);
    }

    public function testReturnsAnEmptyPaginatedListWhenThereAreNoValuesInTheChangeset(): void
    {
        $field     = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $changeset = ChangesetTestBuilder::aChangeset(1)->build();
        $user      = UserTestBuilder::buildWithDefaults();

        $changeset->setFieldValue($field, null);

        $sliced = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        self::assertEmpty($sliced->getArtifacts());
        self::assertEquals(0, $sliced->getTotalSize());
    }

    public function testCreatesAListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $artifact_1 = ArtifactTestBuilder::anArtifact(123)->userCanView($user)->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact(345)->userCanView($user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = [
            $artifact_1,
            $artifact_2,
        ];
        self::assertEquals($expected_artifacts, $artifacts);
    }

    public function testCreatesAPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $artifact_1 = ArtifactTestBuilder::anArtifact(123)->userCanView($user)->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact(345)->userCanView($user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = [
            $artifact_1,
            $artifact_2,
        ];
        self::assertEquals($expected_artifacts, $sliced->getArtifacts());
        self::assertEquals(2, $sliced->getTotalSize());
    }

    public function testCreatesAFirstPageOfPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $artifact_1 = ArtifactTestBuilder::anArtifact(123)->userCanView($user)->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact(345)->userCanView($user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 0);
        $expected_artifacts = [
            $artifact_1,
        ];
        self::assertEquals($expected_artifacts, $sliced->getArtifacts());
        self::assertEquals(2, $sliced->getTotalSize());
    }

    public function testCreatesASecondPageOfPaginatedListOfArtifactsBasedOnTheIdsInTheChangesetField(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $artifact_1 = ArtifactTestBuilder::anArtifact(123)->userCanView($user)->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact(345)->userCanView($user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 1);
        $expected_artifacts = [
            $artifact_2,
        ];
        self::assertEquals($expected_artifacts, $sliced->getArtifacts());
        self::assertEquals(2, $sliced->getTotalSize());
    }

    public function testIgnoresIdsThatDontExist(): void
    {
        $user     = UserTestBuilder::buildWithDefaults();
        $artifact = ArtifactTestBuilder::anArtifact(123)->userCanView($user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact]));

        $non_existing_id = 666;
        $changeset       = $this->givenAChangesetValueWithArtifactIds($field, [123, $non_existing_id]);

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = [$artifact];
        self::assertEquals($expected_artifacts, $artifacts);
    }

    public function testIgnoresInPaginatedListIdsThatDontExist(): void
    {
        $user     = UserTestBuilder::buildWithDefaults();
        $artifact = ArtifactTestBuilder::anArtifact(123)->userCanView($user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact]));

        $non_existing_id = 666;
        $changeset       = $this->givenAChangesetValueWithArtifactIds($field, [123, $non_existing_id]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = [$artifact];
        self::assertEquals($expected_artifacts, $sliced->getArtifacts());
        self::assertEquals(2, $sliced->getTotalSize());
    }

    public function testReturnsOnlyArtifactsAccessibleByGivenUser(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $artifact_1 = ArtifactTestBuilder::anArtifact(123)->userCannotView($user)->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact(345)->userCanView($user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $artifacts          = $field->getLinkedArtifacts($changeset, $user);
        $expected_artifacts = [
            $artifact_2,
        ];
        self::assertEquals($expected_artifacts, $artifacts);
    }

    public function testReturnsOnlyPaginatedArtifactsAccessibleByGivenUser(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $artifact_1 = ArtifactTestBuilder::anArtifact(123)->userCannotView($user)->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact(345)->userCanView($user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 10, 0);
        $expected_artifacts = [
            $artifact_2,
        ];
        self::assertEquals($expected_artifacts, $sliced->getArtifacts());
        self::assertEquals(2, $sliced->getTotalSize());
    }

    public function testReturnsAFirstPageOfOnlyPaginatedArtifactsAccessibleByGivenUser(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $artifact_1 = ArtifactTestBuilder::anArtifact(123)->userCannotView($user)->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact(345)->userCanView($user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->givenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->givenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 0);
        $expected_artifacts = [];
        self::assertEquals($expected_artifacts, $sliced->getArtifacts());
        self::assertEquals(2, $sliced->getTotalSize());
    }

    public function testReturnsASecondPageOfOnlyPaginatedArtifactsAccessibleByGivenUser(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $artifact_1 = ArtifactTestBuilder::anArtifact(123)->userCannotView($user)->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact(345)->userCanView($user)->build();

        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        $field->setArtifactFactory($this->GivenAnArtifactFactory([$artifact_1, $artifact_2]));

        $changeset = $this->GivenAChangesetValueWithArtifactIds($field, [123, 345]);

        $sliced             = $field->getSlicedLinkedArtifacts($changeset, $user, 1, 1);
        $expected_artifacts = [
            $artifact_2,
        ];
        self::assertEquals($expected_artifacts, $sliced->getArtifacts());
        self::assertEquals(2, $sliced->getTotalSize());
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();

        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $value = ['some_value'];

        $field->getFieldDataFromRESTValueByField($value);
    }

    public function testItDoesNotRaiseWarningIfItDoesNotHaveAllInformationToDisplayAnAsyncRenderer(): void
    {
        $this->expectNotToPerformAssertions();
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHTTPREQUEST';
        $field->process(
            $this->createMock(Tracker_IDisplayTrackerLayout::class),
            HTTPRequestBuilder::get()->withAnonymousUser()->withParams([
                'func'          => 'artifactlink-renderer-async',
                'renderer_data' => json_encode(['artifact_id' => 123]),
            ])->build(),
            UserTestBuilder::anAnonymousUser()->build()
        );
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    public function testItReturnsARESTValueEvenIfThereIsNone(): void
    {
        $field     = $this->createPartialMock(ArtifactLinkField::class, ['getValueDao']);
        $value_dao = $this->createMock(ArtifactLinkFieldValueDao::class);
        $field->expects($this->once())->method('getValueDao')->willReturn($value_dao);
        $value_dao->method('searchReverseLinksById')->willReturn(TestHelper::emptyDar());
        $changeset = ChangesetTestBuilder::aChangeset(98451)->build();
        $changeset->setFieldValue($field, null);
        self::assertNotNull($field->getFullRESTValue(UserTestBuilder::buildWithDefaults(), $changeset));
    }

    /**
     * @param int[] $ids
     */
    private function givenAChangesetValueWithArtifactIds(ArtifactLinkField $field, array $ids): Tracker_Artifact_Changeset
    {
        $links = [];
        foreach ($ids as $id) {
            $links[$id] = new Tracker_ArtifactLinkInfo($id, '', 101, 34, 1, null);
        }

        $changeset       = ChangesetTestBuilder::aChangeset(654)->build();
        $changeset_value = ChangesetValueArtifactLinkTestBuilder::aValue(6541, $changeset, $field)->withForwardLinks($links)->build();
        $changeset->setFieldValue($field, $changeset_value);
        return $changeset;
    }

    /**
     * @param Artifact[] $artifacts
     */
    private function givenAnArtifactFactory(array $artifacts): Tracker_ArtifactFactory
    {
        $factory = $this->createMock(Tracker_ArtifactFactory::class);
        $factory->method('getArtifactById')->willReturnCallback(static function (int $id) use ($artifacts): ?Artifact {
            foreach ($artifacts as $artifact) {
                if ($artifact->getId() === $id) {
                    return $artifact;
                }
            }
            return null;
        });
        return $factory;
    }
}
