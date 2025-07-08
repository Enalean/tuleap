<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerReportRESTGetTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private Tracker_Report_REST $report;
    private int $tracker_id = 444;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private PFUser $current_user;


    protected function setUp(): void
    {
        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);

        $this->current_user = UserTestBuilder::buildWithDefaults();

        $this->report = new Tracker_Report_REST(
            $this->current_user,
            TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build(),
            $this->createMock(PermissionsManager::class),
            $this->createMock(Tracker_ReportDao::class),
            $this->formelement_factory
        );

        $query = new ArrayObject(
            [
                'my_field'       => new ArrayObject(
                    [
                        Tracker_Report_REST::VALUE_PROPERTY_NAME    => 'true',
                        Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR,
                    ]
                ),
                'my_other_field' => 'bla',
                '137'            => 'my_value',
            ]
        );

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function testItChoosesTheFormElementIdOverTheShortName(): void
    {
        $this->formelement_factory->method('getFormElementById')->willReturnCallback(fn (mixed $id) => match ($id) {
            137 => IntFieldBuilder::anIntField(137)->withReadPermission($this->current_user, true)->build(),
            default => null,
        });
        $this->formelement_factory
            ->expects($this->exactly(2))
            ->method('getFormElementByName')
            ->willReturn(
                IntFieldBuilder::anIntField(1001)
                    ->withReadPermission($this->current_user, true)
                    ->build()
            );

        $this->report->getCriteria();
    }

    public function testItThrowExceptionIfGivenFormelementsDontExist(): void
    {
        $this->formelement_factory->method('getFormElementById')->willReturnCallback(fn (mixed $id) => match ($id) {
            137 => IntFieldBuilder::anIntField(137)->withReadPermission($this->current_user, true)->build(),
            default => null,
        });
        $this->formelement_factory
            ->expects($this->exactly(2))
            ->method('getFormElementByName')
            ->willReturn(null);

        $this->expectException(Tracker_Report_InvalidRESTCriterionException::class);

        $this->report->getCriteria();
    }

    public function testItFetchesByNameIfTheFormElementByIdDoesNotExist(): void
    {
        $this->formelement_factory->expects($this->exactly(3))->method('getFormElementById')->willReturn(null);

        $this->formelement_factory->expects($this->exactly(3))->method('getFormElementByName')
            ->willReturnCallback(fn (int $tracker_id, mixed $name) => match (true) {
                $tracker_id === 444 && $name === 137 => IntFieldBuilder::anIntField(137)->withReadPermission($this->current_user, true)->build(),
                $tracker_id === 444 && $name === 'my_field' => IntFieldBuilder::anIntField(1001)->withReadPermission($this->current_user, true)->build(),
                $tracker_id === 444 && $name === 'my_other_field' => IntFieldBuilder::anIntField(1002)->withReadPermission($this->current_user, true)->build(),
            });

        $this->report->getCriteria();
    }

    public function testItOnlyAddsCriteriaOnFieldsUserCanSee(): void
    {
        $field_1 = $this->createMock(Tracker_FormElement_Field_Integer::class);
        $field_2 = $this->createMock(Tracker_FormElement_Field_Integer::class);
        $field_3 = $this->createMock(Tracker_FormElement_Field_Integer::class);

        $this->formelement_factory->method('getFormElementById')->willReturnCallback(fn (mixed $id) => match ($id) {
            'my_field' => $field_1,
            'my_other_field' => $field_2,
            137 => $field_3,
        });

        $field_1->method('userCanRead')->willReturn(false);
        $field_2->method('userCanRead')->willReturn(false);
        $field_3->method('userCanRead')->willReturn(true);

        $field_1->method('getId')->willReturn(111);
        $field_2->method('getId')->willReturn(222);
        $field_3->method('getId')->willReturn(333);

        $field_3->method('setCriteriaValueFromREST')->willReturn(true);

        $criteria = $this->report->getCriteria();

        $this->assertCount(1, $criteria);

        $criteria_field_ids = array_keys($criteria);
        $this->assertEqualS(333, $criteria_field_ids[0]);
    }

    public function testItAddsCriteria(): void
    {
        $integer = $this->createMock(Tracker_FormElement_Field_Integer::class);
        $integer->method('getId')->willReturn(22);
        $integer->method('userCanRead')->willReturn(true);

        $label = $this->createMock(TextField::class);
        $label->method('getId')->willReturn(44);
        $label->method('userCanRead')->willReturn(true);

        $this->formelement_factory->method('getFormElementById')->willReturnCallback(fn (mixed $id) => match ($id) {
            137 => $integer,
            default => null,
        });
        $this->formelement_factory->method('getFormElementByName')->willReturnCallback(fn (int $tracker_id, mixed $name) => match ($name) {
            'my_field' => $label,
            'my_other_field' => IntFieldBuilder::anIntField(1001)->withReadPermission($this->current_user, false)->build(),
        });

        $integer->expects($this->once())->method('setCriteriaValueFromREST')->willReturn(true);
        $label->expects($this->once())->method('setCriteriaValueFromREST')->willReturn(false);

        $criteria = $this->report->getCriteria();

        $this->assertCount(1, $criteria);
    }
}
