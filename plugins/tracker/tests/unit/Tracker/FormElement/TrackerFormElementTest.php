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

use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_StaticField_Separator;
use Tracker_FormElementFactory;
use TrackerManager;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;

class TrackerFormElementTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    protected function tearDown(): void
    {
        unset($GLOBALS['HTML']);
    }

    public function testGetOriginalProjectAndOriginalTracker()
    {
        $project = Mockery::mock(Project::class);
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(888);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $original = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $original->shouldReceive('getTracker')->andReturns($tracker);

        $element = $this->GivenAFormElementWithIdAndOriginalField(null, $original);

        $this->assertEquals($tracker, $element->getOriginalTracker());
        $this->assertEquals($project, $element->getOriginalProject());
    }

    public function testGetOriginalFieldIdShouldReturnTheFieldId()
    {
        $original = $this->GivenAFormElementWithIdAndOriginalField(112, null);
        $element  = $this->GivenAFormElementWithIdAndOriginalField(null, $original);
        $this->assertEquals($element->getOriginalFieldId(), 112);
    }

    public function testGetOriginalFieldIdShouldReturn0IfNoOriginalField()
    {
        $element = $this->GivenAFormElementWithIdAndOriginalField(null, null);
        $this->assertEquals($element->getOriginalFieldId(), 0);
    }

    protected function givenAFormElementWithIdAndOriginalField($id, $originalField)
    {
        return new Tracker_FormElement_StaticField_Separator(
            $id,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $originalField
        );
    }

    public function testDisplayUpdateFormShouldDisplayAForm()
    {
        $formElement = $this->GivenAFormElementWithIdAndOriginalField(null, null);
        $factory = Mockery::mock(Tracker_FormElementFactory::class);
        $factory->shouldReceive('getUsedFormElementForTracker')->andReturns([]);
        $factory->shouldReceive('getSharedTargets');
        $tracker = Mockery::mock(Tracker::class);

        $tracker->shouldReceive('getId')->andReturn(888);
        $tracker->shouldReceive('displayAdminFormElementsHeader');
        $tracker->shouldReceive('displayFooter');
        $formElement->setTracker($tracker);
        $formElement->setFormElementFactory($factory);

        $content = $this->WhenIDisplayAdminFormElement($formElement);

        $this->assertMatchesRegularExpression('%Update%', $content);
        $this->assertMatchesRegularExpression('%</form>%', $content);
    }

    private function whenIDisplayAdminFormElement($formElement)
    {
        $GLOBALS['Language']->shouldReceive('getText')->withArgs([
            'plugin_tracker_include_type', 'upd_label', null
        ])->andReturns('Update');
        $GLOBALS['HTML'] = $GLOBALS['Response'];
        $GLOBALS['HTML']->shouldReveive('getFactoryIconUseIt');

        $tracker_manager = Mockery::mock(TrackerManager::class);
        $user            = Mockery::mock(PFUser::class);
        $request         = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('isAjax');
        ob_start();
        $formElement->displayAdminFormElement($tracker_manager, $request, $user);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
