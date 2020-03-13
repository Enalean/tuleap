<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Label\Widget;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class ProjectLabelRequestDataValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Codendi_Request
     */
    private $request;
    /**
     * @var ProjectLabelRequestDataValidator
     */
    private $validator;

    public function setUp() : void
    {
        parent::setUp();
        $this->validator = new ProjectLabelRequestDataValidator();
        $this->request   = \Mockery::spy(\Codendi_Request::class);
    }

    public function testItThrowsAnExceptionWhenLabelDoesNotBelongToProject()
    {
        $this->request->shouldReceive('get')->with('project-labels')->andReturn(array(1, 2));
        $projects_labels = array(
            array(
                'id'         => '1',
                'project_id' => '102',
                'name'       => 'test',
                'is_outline' => '0',
                'color'      => 'fiesta-red'
            ),
            array(
                'id'         => '3',
                'project_id' => '102',
                'name'       => 'test2',
                'is_outline' => '0',
                'color'      => 'acid-green'
            )
        );
        $this->request->shouldReceive('validArray')->andReturn(true);

        $this->expectException(\Tuleap\Label\Widget\ProjectLabelDoesNotBelongToProjectException::class);

        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }

    public function testItThrowsAnExceptionWhenLabelAreNotSent()
    {
        $this->request->shouldReceive('get')->with('project-labels')->andReturn([]);
        $projects_labels = array();

        $this->expectException(\Tuleap\Label\Widget\ProjectLabelAreMandatoryException::class);

        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }

    public function testItThrowsAnExceptionWhenLabelsAreInvalid()
    {
        $this->request->shouldReceive('get')->with('project-labels')->andReturn(array('aa', 'bb'));
        $this->request->shouldReceive('validArray')->andReturn(false);

        $projects_labels = array();

        $this->expectException(\Tuleap\Label\Widget\ProjectLabelAreNotValidException::class);

        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }

    public function testItDoesNotThrowExceptionWhenLabelsAreValid()
    {
        $this->request->shouldReceive('get')->with('project-labels')->andReturn(array(1, 2));
        $projects_labels = array(
            array(
                'id'         => '1',
                'project_id' => '102',
                'name'       => 'test',
                'is_outline' => '0',
                'color'      => 'fiesta-red'
            ),
            array(
                'id'         => '2',
                'project_id' => '102',
                'name'       => 'test2',
                'is_outline' => '0',
                'color'      => 'acid-green'
            )
        );
        $this->request->shouldReceive('validArray')->andReturn(true);

        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }
}
