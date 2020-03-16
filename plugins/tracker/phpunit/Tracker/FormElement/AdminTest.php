<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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


final class Tracker_FormElement_View_AdminTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Tuleap\GlobalLanguageMock, \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testForSharedFieldsItDisplaysOriginalTrackerAndProjectName(): void
    {
        $admin = $this->givenAnAdminWithOriginalProjectAndTracker('Tuleap', 'Bugs');
        $result = $admin->fetchCustomHelpForShared();
        $this->assertRegExp("%Bugs%", $result);
        $this->assertRegExp("%Tuleap%", $result);
        $this->assertRegExp('%<a href="' . TRACKER_BASE_URL . '/\?tracker=101&amp;func=admin-formElement-update&amp;formElement=666"%', $result);
    }

    public function givenAnAdminWithOriginalProjectAndTracker(string $projectName, string $trackerName): Tracker_FormElement_View_Admin
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getPublicName')->andReturns($projectName);

        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getName')->andReturns($trackerName);
        $tracker->shouldReceive('getId')->andReturns(101);
        $tracker->shouldReceive('getProject')->andReturns($project);

        $original = Mockery::mock(Tracker_FormElement_Field_String::class, [666, null, null, null, null, null, null, null, null, null, null, null])->makePartial()->shouldAllowMockingProtectedMethods();
        $original->shouldReceive('getTracker')->andReturn($tracker);

        $element = Mockery::mock(Tracker_FormElement_Field_String::class, [null, null, null, null, null, null, null, null, null, null, null, $original])->makePartial()->shouldAllowMockingProtectedMethods();

        return new Tracker_FormElement_View_Admin($element, array());
    }

    public function testSharedUsageShouldDisplayAllTrackershatShareMe(): void
    {
        $element = $this->givenAnElementWithManyCopies();
        $admin   = new Tracker_FormElement_View_Admin($element, array());
        $content = $admin->fetchSharedUsage();
        $this->assertRegExp('/Canard/', $content);
        $this->assertRegExp('/Saucisse/', $content);
    }

    private function givenAnElementWithManyCopies()
    {
        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getPublicName')->andReturns('Project');

        $element = $this->getStringFileWithId(1, null);
        $element->shouldReceive('getFormElementFactory')->andReturn($factory)->once();

        $tracker1 = \Mockery::spy(\Tracker::class);
        $tracker1->shouldReceive('getId')->andReturns('123');
        $tracker1->shouldReceive('getName')->andReturns('Canard');
        $tracker1->shouldReceive('getProject')->andReturns($project);
        $copy1 = $this->getStringFileWithId(10, $element);
        $copy2 = $this->getStringFileWithId(20, $element);
        $copy1->shouldReceive('getTracker')->andReturn($tracker1);
        $copy2->shouldReceive('getTracker')->andReturn($tracker1);

        $tracker3 = \Mockery::spy(\Tracker::class);
        $tracker3->shouldReceive('getId')->andReturns('124');
        $tracker3->shouldReceive('getName')->andReturns('Saucisse');
        $tracker3->shouldReceive('getProject')->andReturns($project);
        $copy3 = $this->getStringFileWithId(30, $element);
        $copy3->shouldReceive('getTracker')->andReturn($tracker3);

        $factory->shouldReceive('getSharedTargets')->with($element)->andReturns(array($copy1, $copy2, $copy3));
        return $element;
    }

    /**
     * @return \Mockery\Mock |Tracker_FormElement_Field_String
     */
    private function getStringFileWithId(int $id, ?Tracker_FormElement_Field_String $original_field)
    {
        return Mockery::mock(
            Tracker_FormElement_Field_String::class,
            [$id, null, null, null, null, null, null, null, null, null, null, $original_field]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }
}
