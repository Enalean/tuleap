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
final class Tracker_Report_RESTTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Tracker_Report_REST
     */
    private $report;

    protected function setUp(): void
    {
        $current_user        = \Mockery::spy(\PFUser::class);
        $tracker             = \Mockery::spy(\Tracker::class);
        $permissions_manager = \Mockery::spy(\PermissionsManager::class);
        $dao                 = \Mockery::spy(\Tracker_ReportDao::class);
        $this->formelement_factory = \Mockery::mock(\Tracker_FormElementFactory::class);

        $this->report = new Tracker_Report_REST($current_user, $tracker, $permissions_manager, $dao, $this->formelement_factory);
    }

    public function testItThrowsAnExceptionForBadJSON(): void
    {
        $this->expectException(\Tracker_Report_InvalidRESTCriterionException::class);

        $this->report->setRESTCriteria("{fvf");
    }

    public function testItThrowsAnExceptionForAnInvalidQueryWithMissingOperator(): void
    {
        $this->expectException(\Tracker_Report_InvalidRESTCriterionException::class);

        $query = new ArrayObject(array(
            "my_field" => new ArrayObject(array(
                Tracker_Report_REST::VALUE_PROPERTY_NAME => "true"
            ))
        ));

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function testItThrowsAnExceptionForAnInvalidQueryWithMissingValue(): void
    {
        $this->expectException(\Tracker_Report_InvalidRESTCriterionException::class);

        $query = new ArrayObject(array(
            "my_field" => new ArrayObject(array(
                Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR
            ))
        ));

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function testItThrowsAnExceptionForAnInvalidQueryWithInvlidOperator(): void
    {
        $this->expectException(\Tracker_Report_InvalidRESTCriterionException::class);

        $query = new ArrayObject(array(
            "my_field" => new ArrayObject(array(
                Tracker_Report_REST::VALUE_PROPERTY_NAME    => "true",
                Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR . 'xxx'
            ))
        ));

        $this->report->setRESTCriteria(json_encode($query));
    }

    public function testItTransformsBasicCriteriaToTheCorrectFormat(): void
    {
        $query = new ArrayObject(array(
            "my_field" => new ArrayObject(array(
                Tracker_Report_REST::VALUE_PROPERTY_NAME    => "true",
                Tracker_Report_REST::OPERATOR_PROPERTY_NAME => Tracker_Report_REST::DEFAULT_OPERATOR
            )),
            "my_other_field" => "bla"
        ));

        $this->formelement_factory->shouldReceive('getFormElementById')->twice();
        $this->formelement_factory->shouldReceive('getFormElementByName')->twice();

        $this->report->setRESTCriteria(json_encode($query));
        $this->report->getCriteria();
    }
}
