<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

require_once __DIR__ . '/bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectLabelRequestDataValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProjectLabelRequestDataValidator $validator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Codendi_Request
     */
    private $request;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProjectLabelRequestDataValidator();
        $this->request   = $this->createMock(\Codendi_Request::class);
    }

    public function testItThrowsAnExceptionWhenLabelDoesNotBelongToProject(): void
    {
        $this->request->method('get')->with('project-labels')->willReturn([1, 2]);
        $projects_labels = [
            [
                'id'         => '1',
                'project_id' => '102',
                'name'       => 'test',
                'is_outline' => '0',
                'color'      => 'fiesta-red',
            ],
            [
                'id'         => '3',
                'project_id' => '102',
                'name'       => 'test2',
                'is_outline' => '0',
                'color'      => 'acid-green',
            ],
        ];
        $this->request->method('validArray')->willReturn(true);

        $this->expectException(\Tuleap\Label\Widget\ProjectLabelDoesNotBelongToProjectException::class);

        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }

    public function testItThrowsAnExceptionWhenLabelAreNotSent(): void
    {
        $this->request->method('get')->with('project-labels')->willReturn([]);
        $projects_labels = [];

        $this->expectException(\Tuleap\Label\Widget\ProjectLabelAreMandatoryException::class);

        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }

    public function testItThrowsAnExceptionWhenLabelsAreInvalid(): void
    {
        $this->request->method('get')->with('project-labels')->willReturn(['aa', 'bb']);
        $this->request->method('validArray')->willReturn(false);

        $projects_labels = [];

        $this->expectException(\Tuleap\Label\Widget\ProjectLabelAreNotValidException::class);

        $this->validator->validateDataFromRequest($this->request, $projects_labels);
    }

    public function testItDoesNotThrowExceptionWhenLabelsAreValid(): void
    {
        $this->request->method('get')->with('project-labels')->willReturn([1, 2]);
        $projects_labels = [
            [
                'id'         => '1',
                'project_id' => '102',
                'name'       => 'test',
                'is_outline' => '0',
                'color'      => 'fiesta-red',
            ],
            [
                'id'         => '2',
                'project_id' => '102',
                'name'       => 'test2',
                'is_outline' => '0',
                'color'      => 'acid-green',
            ],
        ];
        $this->request->method('validArray')->willReturn(true);

        $this->validator->validateDataFromRequest($this->request, $projects_labels);

        $this->expectNotToPerformAssertions();
    }
}
