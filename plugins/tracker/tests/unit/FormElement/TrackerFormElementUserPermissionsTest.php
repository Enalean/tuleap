<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Tracker_FormElement_Field_String;
use Tracker_Workflow_WorkflowUser;
use Tuleap\GlobalLanguageMock;

final class TrackerFormElementUserPermissionsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private $form_element;
    private $workflow_user;

    protected function setUp(): void
    {
        $this->form_element = $this->createPartialMock(Tracker_FormElement_Field_String::class, ['getId', 'getLabel', 'getName']);
        $this->form_element->method('getId')->willReturn(300);
        $this->form_element->method('getLabel')->willReturn('My field');
        $this->form_element->method('getName')->willReturn('my_field');

        $this->workflow_user = new Tracker_Workflow_WorkflowUser();
    }

    public function testItGrantsReadAccessToWorkflowUser()
    {
        $this->assertTrue($this->form_element->userCanRead($this->workflow_user));
    }

    public function testItGrantsUpdateAccessToWorkflowUser()
    {
        $this->assertTrue($this->form_element->userCanUpdate($this->workflow_user));
    }

    public function testItGrantsSubmitAccessToWorkflowUser()
    {
        $this->assertTrue($this->form_element->userCanSubmit($this->workflow_user));
    }
}
