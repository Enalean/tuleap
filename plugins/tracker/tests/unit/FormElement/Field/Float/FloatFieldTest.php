<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\FormElement\Field\Float;

use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use TestHelper;
use Tracker_Artifact_ChangesetValue_Float;
use Tracker_Report_Criteria;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\FloatingPointNumber\FloatValueDao;
use Tuleap\Tracker\Semantic\Timeframe\ArtifactTimeframeHelper;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueFloatTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\ReportTestBuilder;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class FloatFieldTest extends TestCase
{
    use GlobalResponseMock;

    private UserManager&MockObject $user_manager;

    #[Override]
    protected function setUp(): void
    {
        $this->user_manager = $this->createMock(UserManager::class);

        UserManager::setInstance($this->user_manager);
    }

    #[Override]
    protected function tearDown(): void
    {
        UserManager::clearInstance();
    }

    public function testNoDefaultValue(): void
    {
        $float_field = FloatFieldBuilder::aFloatField(456)->build();
        self::assertFalse($float_field->hasDefaultValue());
    }

    public function testDefaultValue(): void
    {
        $float_field = FloatFieldBuilder::aFloatField(456)->withSpecificProperty('default_value', ['value' => 12.34])->build();
        self::assertTrue($float_field->hasDefaultValue());
        self::assertEquals(12.34, $float_field->getDefaultValue());
    }

    public function testGetChangesetValue(): void
    {
        $value_dao = $this->createMock(FloatValueDao::class);
        $value_dao->method('searchById')->willReturn(
            TestHelper::arrayToDar(['id' => 123, 'field_id' => 1, 'value' => '1.003'])
        );

        $float_field = $this->createPartialMock(FloatField::class, ['getValueDao']);
        $float_field->method('getValueDao')->willReturn($value_dao);

        self::assertInstanceOf(
            Tracker_Artifact_ChangesetValue_Float::class,
            $float_field->getChangesetValue(ChangesetTestBuilder::aChangeset(987)->build(), 123, false)
        );
    }

    public function testGetChangesetValueDoesNotExist(): void
    {
        $value_dao = $this->createMock(FloatValueDao::class);
        $value_dao->method('searchById')->willReturn(TestHelper::emptyDar());

        $float_field = $this->createPartialMock(FloatField::class, ['getValueDao']);
        $float_field->method('getValueDao')->willReturn($value_dao);

        self::assertNull($float_field->getChangesetValue(ChangesetTestBuilder::aChangeset(987)->build(), 123, false));
    }

    public function testIsValidRequiredField(): void
    {
        $float_field = FloatFieldBuilder::aFloatField(456)->thatIsRequired()->build();
        $artifact    = ArtifactTestBuilder::anArtifact(963)->build();
        self::assertTrue($float_field->isValid($artifact, 2));
        self::assertTrue($float_field->isValid($artifact, 789));
        self::assertTrue($float_field->isValid($artifact, 1.23));
        self::assertTrue($float_field->isValid($artifact, -1.45));
        self::assertTrue($float_field->isValid($artifact, 0));
        self::assertTrue($float_field->isValid($artifact, 0.0000));
        self::assertTrue($float_field->isValid($artifact, '56.789'));
        self::assertFalse($float_field->isValid($artifact, 'toto'));
        self::assertFalse($float_field->isValid($artifact, '12toto'));
        self::assertFalse($float_field->isValid($artifact, []));
        self::assertFalse($float_field->isValid($artifact, [1]));
        self::assertFalse($float_field->isValidRegardingRequiredProperty($artifact, ''));
        self::assertFalse($float_field->isValidRegardingRequiredProperty($artifact, null));
    }

    public function testIsValidNotRequiredField(): void
    {
        $float_field = FloatFieldBuilder::aFloatField(456)->thatIsRequired()->build();
        $artifact    = ArtifactTestBuilder::anArtifact(963)->build();
        self::assertTrue($float_field->isValid($artifact, ''));
        self::assertTrue($float_field->isValid($artifact, null));
    }

    public function testGetFieldData(): void
    {
        $float_field = FloatFieldBuilder::aFloatField(456)->build();
        self::assertEquals('3.14159', $float_field->getFieldData('3.14159'));
    }

    public function testFetchChangesetValue(): void
    {
        $float_field = FloatFieldBuilder::aFloatField(456)->build();
        self::assertSame('3.1416', $float_field->fetchChangesetValue(123, 456, 3.14159));
        self::assertSame('0', $float_field->fetchChangesetValue(123, 456, 0));
        self::assertSame('2', $float_field->fetchChangesetValue(123, 456, 2));
        self::assertSame('', $float_field->fetchChangesetValue(123, 456, null));
    }

    public function testItSearchOnZeroValue(): void
    {
        $float_field = $this->createPartialMock(FloatField::class, ['isUsed', 'getCriteriaValue']);
        $criteria    = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $float_field, 24, false);

        $float_field->method('isUsed')->willReturn(true);
        $float_field->method('getCriteriaValue')->willReturn(0);

        self::assertFalse($float_field->getCriteriaFromWhere($criteria)->isNothing());
    }

    public function testItSearchOnCustomQuery(): void
    {
        $float_field = $this->createPartialMock(FloatField::class, ['isUsed', 'getCriteriaValue']);
        $criteria    = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $float_field, 24, false);

        $float_field->method('isUsed')->willReturn(true);
        $float_field->method('getCriteriaValue')->willReturn('>1');

        self::assertFalse($float_field->getCriteriaFromWhere($criteria)->isNothing());
    }

    public function testItDoesntSearchOnEmptyString(): void
    {
        $float_field = $this->createPartialMock(FloatField::class, ['isUsed', 'getCriteriaValue']);
        $criteria    = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $float_field, 24, false);

        $float_field->method('isUsed')->willReturn(true);
        $float_field->method('getCriteriaValue')->willReturn('');

        self::assertTrue($float_field->getCriteriaFromWhere($criteria)->isNothing());
    }

    public function testItDoesntSearchOnNullCriteria(): void
    {
        $float_field = $this->createPartialMock(FloatField::class, ['isUsed', 'getCriteriaValue']);
        $criteria    = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $float_field, 24, false);

        $float_field->method('isUsed')->willReturn(true);
        $float_field->method('getCriteriaValue')->willReturn(null);

        self::assertTrue($float_field->getCriteriaFromWhere($criteria)->isNothing());
    }

    public function testItFetchCriteriaAndSetValueZero(): void
    {
        $float_field = $this->createPartialMock(FloatField::class, ['getCriteriaValue']);
        $criteria    = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $float_field, 24, false);

        $float_field->setId(1);
        $float_field->method('getCriteriaValue')->willReturn(0);

        self::assertEquals(
            '<input data-test="float-report-criteria" type="text" name="criteria[1]" id="tracker_report_criteria_1" value="0" />',
            $float_field->fetchCriteriaValue($criteria)
        );
    }

    public function testItFetchCriteriaAndLeaveItEmptyValue(): void
    {
        $float_field = $this->createPartialMock(FloatField::class, ['getCriteriaValue']);
        $criteria    = new Tracker_Report_Criteria(12, ReportTestBuilder::aPublicReport()->withId(1)->build(), $float_field, 24, false);

        $float_field->setId(1);
        $float_field->method('getCriteriaValue')->willReturn('');

        self::assertEquals(
            '<input data-test="float-report-criteria" type="text" name="criteria[1]" id="tracker_report_criteria_1" value="" />',
            $float_field->fetchCriteriaValue($criteria)
        );
    }

    public function testTheValueIndexedByFieldNameIsReturned(): void
    {
        $float_field = FloatFieldBuilder::aFloatField(876)->build();
        $value       = [
            'field_id' => 876,
            'value'    => 3.14,
        ];

        self::assertEquals(3.14, $float_field->getFieldDataFromRESTValueByField($value));
    }

    public function testItDisplaysTheFloatValueInReadOnly(): void
    {
        $float_field = $this->createPartialMock(FloatField::class, ['getArtifactTimeframeHelper']);

        $timeframe_helper = $this->createMock(ArtifactTimeframeHelper::class);
        $float_field->expects($this->once())->method('getArtifactTimeframeHelper')->willReturn($timeframe_helper);

        $timeframe_helper->expects($this->once())->method('artifactHelpShouldBeShownToUser')->willReturn(false);

        $artifact        = ArtifactTestBuilder::anArtifact(963)->build();
        $changeset_value = ChangesetValueFloatTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(123)->build(), $float_field)
            ->withValue(5.1)->build();

        $this->user_manager->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());

        $html_value_read_only = $float_field->fetchArtifactValueReadOnly($artifact, $changeset_value);

        self::assertSame('5.1', $html_value_read_only);
    }

    public function testItDisplaysTheFloatValue0InReadOnly(): void
    {
        $float_field = $this->createPartialMock(FloatField::class, ['getArtifactTimeframeHelper']);

        $timeframe_helper = $this->createMock(ArtifactTimeframeHelper::class);
        $float_field->expects($this->once())->method('getArtifactTimeframeHelper')->willReturn($timeframe_helper);

        $timeframe_helper->expects($this->once())->method('artifactHelpShouldBeShownToUser')->willReturn(false);

        $artifact        = ArtifactTestBuilder::anArtifact(963)->build();
        $changeset_value = ChangesetValueFloatTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(123)->build(), $float_field)
            ->withValue(0)->build();

        $this->user_manager->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());

        $html_value_read_only = $float_field->fetchArtifactValueReadOnly($artifact, $changeset_value);

        self::assertSame('0', $html_value_read_only);
    }

    public function testItDisplaysEmptyMessageIfNoChangesetValue(): void
    {
        $float_field = FloatFieldBuilder::aFloatField(456)->build();

        $artifact = ArtifactTestBuilder::anArtifact(963)->build();

        $html_value_read_only = $float_field->fetchArtifactValueReadOnly($artifact, null);

        self::assertSame("<span class='empty_value'>Empty</span>", $html_value_read_only);
    }

    public function testItDisplaysEmptyMessageIfNoChangesetFloatValue(): void
    {
        $float_field = FloatFieldBuilder::aFloatField(456)->build();

        $artifact        = ArtifactTestBuilder::anArtifact(963)->build();
        $changeset_value = ChangesetValueFloatTestBuilder::aValue(1, ChangesetTestBuilder::aChangeset(123)->build(), $float_field)
            ->withValue(null)->build();

        $html_value_read_only = $float_field->fetchArtifactValueReadOnly($artifact, $changeset_value);

        self::assertSame("<span class='empty_value'>Empty</span>", $html_value_read_only);
    }
}
