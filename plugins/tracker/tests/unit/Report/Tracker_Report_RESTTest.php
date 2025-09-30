<?php
/**
 * Copyright (c) Enalean, 2014 - present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Report_RESTTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    /**
     * @var Tracker_Report_REST
     */
    private $report;
    private Tracker_FormElementFactory&\PHPUnit\Framework\MockObject\MockObject $formelement_factory;
    private Tracker $tracker;

    #[\Override]
    protected function setUp(): void
    {
        $current_user              = UserTestBuilder::aUser()->build();
        $this->tracker             = TrackerTestBuilder::aTracker()->withId(122)->build();
        $permissions_manager       = $this->createStub(\PermissionsManager::class);
        $dao                       = $this->createStub(\Tracker_ReportDao::class);
        $this->formelement_factory = $this->createMock(\Tracker_FormElementFactory::class);

        $this->report = new Tracker_Report_REST(
            $current_user,
            $this->tracker,
            $permissions_manager,
            $dao,
            $this->formelement_factory
        );
    }

    public function testItThrowsAnExceptionForBadJSON(): void
    {
        $this->expectException(\Tracker_Report_InvalidRESTCriterionException::class);

        $this->report->setRESTCriteria('{fvf');
    }

    public function testItThrowsAnExceptionForAnInvalidQueryWithMissingOperator(): void
    {
        $this->expectException(\Tracker_Report_InvalidRESTCriterionException::class);

        $query = new ArrayObject(
            [
                'my_field' => new ArrayObject(
                    [
                        Tracker_Report_REST::VALUE_PROPERTY_NAME => 'true',
                    ]
                ),
            ]
        );

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function testItThrowsAnExceptionForAnInvalidQueryWithMissingValue(): void
    {
        $this->expectException(\Tracker_Report_InvalidRESTCriterionException::class);

        $query = new ArrayObject(
            [
                'my_field' => new ArrayObject(
                    [
                        Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR,
                    ]
                ),
            ]
        );

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function testItThrowsAnExceptionForAnInvalidQueryWithInvalidOperator(): void
    {
        $this->expectException(\Tracker_Report_InvalidRESTCriterionException::class);

        $query = new ArrayObject(
            [
                'my_field' => new ArrayObject(
                    [
                        Tracker_Report_REST::VALUE_PROPERTY_NAME    => 'true',
                        Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR . 'xxx',
                    ]
                ),
            ]
        );

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function testItTransformsBasicCriteriaToTheCorrectFormat(): void
    {
        $field_1 = $this->createStub(SelectboxField::class);
        $field_2 = $this->createStub(SelectboxField::class);

        $field_1->method('userCanRead')->willReturn(true);
        $field_1->method('getId')->willReturn(1);
        $field_1->method('setCriteriaValueFromREST')->willReturn(true);

        $field_2->method('userCanRead')->willReturn(true);
        $field_2->method('getId')->willReturn(2);
        $field_2->method('setCriteriaValueFromREST')->willReturn(true);

        $query = new ArrayObject(
            [
                'my_field'       => new ArrayObject(
                    [
                        Tracker_Report_REST::VALUE_PROPERTY_NAME    => 'true',
                        Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR,
                    ]
                ),
                'my_other_field' => 'bla',
            ]
        );

        $this->formelement_factory->expects($this->exactly(2))->method('getFormElementById');

        $this->formelement_factory->method('getFormElementByName')->willReturnCallback(
            fn (int $tracker_id, string $field_name): TrackerField => match (true) {
                $this->tracker->getId() === $tracker_id && 'my_field' === $field_name => $field_1,
                $this->tracker->getId() === $tracker_id && 'my_other_field' === $field_name => $field_2,
            }
        );

        $this->report->setRESTCriteria(json_encode($query));
        $this->report->getCriteria();
    }

    public function testItThrowExceptionIfGivenFormelementsDontExist(): void
    {
        $field_1 = $this->createStub(SelectboxField::class);

        $query = new ArrayObject(
            [
                'my_field'       => new ArrayObject(
                    [
                        Tracker_Report_REST::VALUE_PROPERTY_NAME    => 'true',
                        Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR,
                    ]
                ),
                'my_other_field' => 'bla',
                'another_field'  => 'nope',
            ]
        );

        $this->formelement_factory->expects($this->exactly(3))->method('getFormElementById');

        $this->formelement_factory->method('getFormElementByName')->willReturnCallback(
            fn (int $tracker_id, string $field_name): ?TrackerField => match (true) {
                $this->tracker->getId() === $tracker_id && 'my_field' === $field_name => null,
                $this->tracker->getId() === $tracker_id && 'my_other_field' === $field_name => $field_1,
                $this->tracker->getId() === $tracker_id && 'another_field' === $field_name => null,
            }
        );

        $this->expectException(Tracker_Report_InvalidRESTCriterionException::class);

        $this->report->setRESTCriteria(json_encode($query));
        $this->report->getCriteria();
    }
}
