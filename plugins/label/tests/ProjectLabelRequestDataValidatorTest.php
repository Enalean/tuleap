<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use TuleapTestCase;

require_once('bootstrap.php');

class ProjectLabelRequestDataValidatorTest extends TuleapTestCase
{
    /**
     * @var \Codendi_Request
     */
    private $request;
    /**
     * @var ProjectLabelRequestDataValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new ProjectLabelRequestDataValidator();
        $this->request   = mock('Codendi_Request');
    }

    public function itThrowsAnExceptionWhenLabelDoesNotBelongToProject()
    {
        stub($this->request)->get('project-labels')->returns(array(1, 2));
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
        stub($this->request)->validArray()->returns(true);

        $this->expectException('Tuleap\Label\Widget\ProjectLabelDoesNotBelongToProjectException');
        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }

    public function itThrowsAnExceptionWhenLabelAreNotSent()
    {
        stub($this->request)->get('project-labels')->returns(array());
        $projects_labels = array();

        $this->expectException('Tuleap\Label\Widget\ProjectLabelAreMandatoryException');
        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }

    public function itThrowsAnExceptionWhenLabelsAreInvalid()
    {
        stub($this->request)->get('project-labels')->returns(array('aa', 'bb'));
        stub($this->request)->validArray()->returns(false);

        $projects_labels = array();

        $this->expectException('Tuleap\Label\Widget\ProjectLabelAreNotValidException');
        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }

    public function itDoesNotThrowExceptionWhenLabelsAreValid()
    {
        stub($this->request)->get('project-labels')->returns(array(1, 2));
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
        stub($this->request)->validArray()->returns(true);

        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }
}
