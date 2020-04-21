<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_URLTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturns(666);

        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);
        $af = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $af->shouldReceive('getArtifactById')->with('1')->andReturns($this->artifact);

        $this->report = \Mockery::spy(\Tracker_Report::class);
        $rf = \Mockery::spy(\Tracker_ReportFactory::class);
        $rf->shouldReceive('getReportById')->with('2', $this->user->getId(), true)->andReturns($this->report);

        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->tracker->shouldReceive('isActive')->andReturns(true);
        $this->tracker->shouldReceive('userCanView')->andReturns(true);
        $tf = \Mockery::spy(\TrackerFactory::class);
        $tf->shouldReceive('getTrackerById')->with(3)->andReturns($this->tracker);

        $this->formElement = \Mockery::spy(\Tracker_FormElement_Interface::class);
        $ff = \Mockery::spy(\Tracker_FormElementFactory::class);
        $ff->shouldReceive('getFormElementById')->with('4')->andReturns($this->formElement);

        $this->artifact->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->report->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->formElement->shouldReceive('getTracker')->andReturns($this->tracker);

        $this->url = \Mockery::mock(\Tracker_URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->url->shouldReceive('getTrackerFactory')->andReturns($tf);
        $this->url->shouldReceive('getTracker_FormElementFactory')->andReturns($ff);
        $this->url->shouldReceive('getArtifactFactory')->andReturns($af);
        $this->url->shouldReceive('getArtifactReportFactory')->andReturns($rf);
    }

    public function testGetArtifact()
    {
        $request_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_artifact->shouldReceive('get')->with('aid')->andReturns('1');
        $request_artifact->shouldReceive('get')->with('report')->andReturns('2');
        $request_artifact->shouldReceive('get')->with('tracker')->andReturns(3);
        $request_artifact->shouldReceive('get')->with('formElement')->andReturns('4');
        $request_artifact->shouldReceive('get')->with('group_id')->andReturns('5');
        $this->assertInstanceOf(
            Tracker_Artifact::class,
            $this->url->getDispatchableFromRequest($request_artifact, $this->user),
        );
    }

    public function testGetReport()
    {
        $request_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_artifact->shouldReceive('get')->with('report')->andReturns('2');
        $request_artifact->shouldReceive('get')->with('tracker')->andReturns(3);
        $request_artifact->shouldReceive('get')->with('formElement')->andReturns('4');
        $request_artifact->shouldReceive('get')->with('group_id')->andReturns('5');
        $this->assertInstanceOf(
            Tracker_Report::class,
            $this->url->getDispatchableFromRequest($request_artifact, $this->user),
        );
    }

    public function testGetTracker()
    {
        $request_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_artifact->shouldReceive('get')->with('tracker')->andReturns(3);
        $request_artifact->shouldReceive('get')->with('formElement')->andReturns('4');
        $request_artifact->shouldReceive('get')->with('group_id')->andReturns('5');
        $this->assertInstanceOf(
            Tracker::class,
            $this->url->getDispatchableFromRequest($request_artifact, $this->user)
        );
    }

    public function testGetTrackerWithAtid()
    {
        $request_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_artifact->shouldReceive('get')->with('atid')->andReturns(3);
        $request_artifact->shouldReceive('get')->with('formElement')->andReturns('4');
        $request_artifact->shouldReceive('get')->with('group_id')->andReturns('5');
        $this->assertInstanceOf(
            Tracker::class,
            $this->url->getDispatchableFromRequest($request_artifact, $this->user)
        );
    }

    public function testGetField()
    {
        $request_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_artifact->shouldReceive('get')->with('formElement')->andReturns('4');
        $request_artifact->shouldReceive('get')->with('group_id')->andReturns('5');
        $this->assertInstanceOf(
            Tracker_FormElement_Interface::class,
            $this->url->getDispatchableFromRequest($request_artifact, $this->user),
        );
    }

    public function testGetNotMatchingElement()
    {
        $request_artifact = \Mockery::spy(\Codendi_Request::class);
        $request_artifact->shouldReceive('get')->with('group_id')->andReturns('5');

        $this->expectException(Tracker_NoMachingResourceException::class);

        $this->url->getDispatchableFromRequest($request_artifact, $this->user);
    }
}
