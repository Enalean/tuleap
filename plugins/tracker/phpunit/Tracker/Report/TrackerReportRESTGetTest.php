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

final class TrackerReportRESTGetTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Tracker_Report_REST
     */
    private $report;
    /**
     * @var int
     */
    private $tracker_id;


    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;


    protected function setUp(): void
    {
        $current_user              = \Mockery::spy(\PFUser::class);
        $permissions_manager       = \Mockery::spy(\PermissionsManager::class);
        $dao                       = \Mockery::spy(\Tracker_ReportDao::class);
        $tracker                   = \Mockery::spy(\Tracker::class);
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $this->tracker_id = 444;
        $tracker->shouldReceive('getId')->andReturns($this->tracker_id);

        $this->report = new Tracker_Report_REST(
            $current_user,
            $tracker,
            $permissions_manager,
            $dao,
            $this->formelement_factory
        );

        $query = new ArrayObject(
            [
                "my_field"       => new ArrayObject(
                    [
                        Tracker_Report_REST::VALUE_PROPERTY_NAME    => "true",
                        Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR
                    ]
                ),
                "my_other_field" => "bla",
                "137"            => "my_value"
            ]
        );

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function testItChoosesTheFormElementIdOverTheShortName(): void
    {
        $this->formelement_factory->shouldReceive('getFormElementById')->with('my_field')->andReturns(null);
        $this->formelement_factory->shouldReceive('getFormElementById')->with('my_other_field')->andReturns(null);
        $this->formelement_factory->shouldReceive('getFormElementById')->with(137)->andReturns(
            \Mockery::spy(\Tracker_FormElement_Field_Integer::class)
        );
        $this->formelement_factory->shouldReceive('getFormElementByName')->times(2);

        $this->report->getCriteria();
    }

    public function testItFetchesByNameIfTheFormElementByIdDoesNotExist(): void
    {
        $this->formelement_factory->shouldReceive('getFormElementById')->with(137)->andReturns(null)->once();
        $this->formelement_factory->shouldReceive('getFormElementByName')->with(444, 137)->once();

        $this->formelement_factory->shouldReceive('getFormElementById')->with('my_field')->andReturns(null)->once();
        $this->formelement_factory->shouldReceive('getFormElementByName')->with(444, 'my_field')->once();

        $this->formelement_factory->shouldReceive('getFormElementById')->with('my_other_field')->andReturns(null)->once(
        );
        $this->formelement_factory->shouldReceive('getFormElementByName')->with(444, 'my_other_field')->once();

        $this->report->getCriteria();
    }

    public function testItOnlyAddsCriteriaOnFieldsUserCanSee(): void
    {
        $field_1 = \Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $field_2 = \Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $field_3 = \Mockery::spy(\Tracker_FormElement_Field_Integer::class);

        $this->formelement_factory->shouldReceive('getFormElementById')->with('my_field')->andReturns($field_1);
        $this->formelement_factory->shouldReceive('getFormElementById')->with('my_other_field')->andReturns($field_2);
        $this->formelement_factory->shouldReceive('getFormElementById')->with(137)->andReturns($field_3);

        $field_1->shouldReceive('userCanRead')->andReturns(false);
        $field_2->shouldReceive('userCanRead')->andReturns(false);
        $field_3->shouldReceive('userCanRead')->andReturns(true);

        $field_1->shouldReceive('getId')->andReturns(111);
        $field_2->shouldReceive('getId')->andReturns(222);
        $field_3->shouldReceive('getId')->andReturns(333);

        $field_3->shouldReceive('setCriteriaValueFromREST')->andReturns(true);

        $criteria = $this->report->getCriteria();

        $this->assertCount(1, $criteria);

        $criteria_field_ids = array_keys($criteria);
        $this->assertEqualS(333, $criteria_field_ids[0]);
    }

    public function testItAddsCriteria(): void
    {
        $integer = \Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $integer->shouldReceive('getId')->andReturns(22);
        $integer->shouldReceive('userCanRead')->andReturns(true);

        $label = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        $label->shouldReceive('getId')->andReturns(44);
        $label->shouldReceive('userCanRead')->andReturns(true);

        $this->formelement_factory->shouldReceive('getFormElementById')->with(137)->andReturns($integer);
        $this->formelement_factory->shouldReceive('getFormElementByName')->with(
            $this->tracker_id,
            "my_field"
        )->andReturns($label);
        $this->formelement_factory->shouldReceive('getFormElementByName')->with("my_other_field")->andReturns(null);

        $integer->shouldReceive('setCriteriaValueFromREST')->once()->andReturns(true);
        $label->shouldReceive('setCriteriaValueFromREST')->once()->andReturns(false);

        $criteria = $this->report->getCriteria();

        $this->assertCount(1, $criteria);
    }
}
