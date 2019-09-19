<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once __DIR__.'/../../bootstrap.php';

class Tracker_FormElement_View_AdminTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $expected_url = TRACKER_BASE_URL .'/?tracker=101&amp;func=admin-formElement-update&amp;formElement=666';
        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_tracker_include_type', 'field_copied_from', array('Bugs', 'Tuleap', $expected_url))->andReturns('This field is shared from tracker <a href="'. $expected_url .'">Bugs from project Tuleap</a>');
    }

    public function tearDown()
    {
         parent::tearDown();
    }

    public function testForSharedFieldsItDisplaysOriginalTrackerAndProjectName()
    {
        $admin = $this->GivenAnAdminWithOriginalProjectAndTracker('Tuleap', 'Bugs');
        $result = $admin->fetchCustomHelpForShared();
        $this->assertPattern("%Bugs%", $result);
        $this->assertPattern("%Tuleap%", $result);
        $this->assertPattern('%<a href="'. TRACKER_BASE_URL .'/\?tracker=101&amp;func=admin-formElement-update&amp;formElement=666"%', $result);
    }

    public function GivenAnAdminWithOriginalProjectAndTracker($projectName, $trackerName)
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getPublicName')->andReturns($projectName);

        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getName')->andReturns($trackerName);
        $tracker->shouldReceive('getId')->andReturns(101);
        $tracker->shouldReceive('getProject')->andReturns($project);

        $original = new FakeFormElement(666, null, null, null, null, null, null, null, null, null, null, null);
        $original->setTracker($tracker);

        $element = new FakeFormElement(null, null, null, null, null, null, null, null, null, null, null, $original);
        return new Tracker_FormElement_View_Admin($element, array());
    }

    public function testSharedUsageShouldDisplayAllTrackershatShareMe()
    {
        $element = $this->GivenAnElementWithManyCopies();
        $admin   = new Tracker_FormElement_View_Admin($element, array());
        $content = $admin->fetchSharedUsage();
        $this->assertPattern('/Canard/', $content);
        $this->assertPattern('/Saucisse/', $content);
    }

    private function GivenAnElementWithManyCopies()
    {
        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getPublicName')->andReturns('Project');

        $element = new FakeFormElement(1, null, null, null, null, null, null, null, null, null, null, null);
        $element->setFormElementFactory($factory);

        $tracker1 = \Mockery::spy(\Tracker::class);
        $tracker1->shouldReceive('getId')->andReturns('123');
        $tracker1->shouldReceive('getName')->andReturns('Canard');
        $tracker1->shouldReceive('getProject')->andReturns($project);
        $copy1    = new FakeFormElement(10, null, null, null, null, null, null, null, null, null, null, $element);
        $copy2    = new FakeFormElement(20, null, null, null, null, null, null, null, null, null, null, $element);
        $copy1->setTracker($tracker1);
        $copy2->setTracker($tracker1);

        $tracker3 = \Mockery::spy(\Tracker::class);
        $tracker3->shouldReceive('getId')->andReturns('124');
        $tracker3->shouldReceive('getName')->andReturns('Saucisse');
        $tracker3->shouldReceive('getProject')->andReturns($project);
        $copy3    = new FakeFormElement(30, null, null, null, null, null, null, null, null, null, null, $element);
        $copy3->setTracker($tracker3);

        $factory->shouldReceive('getSharedTargets')->with($element)->andReturns(array($copy1, $copy2, $copy3));
        return $element;
    }
}

class FakeFormElement extends Tracker_FormElement_Field_String
{

    public static function getFactoryIconUseIt()
    {
        // just here to avoid undesired behaviour in test
    }

    public static function getFactoryLabel()
    {
    }

    public function setSharedCopies($fields)
    {
        $this->sharedCopies = $fields;
    }
}
