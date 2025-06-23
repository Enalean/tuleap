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

declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_URLTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private PFUser $user;
    private Tracker_URL&MockObject $url;

    protected function setUp(): void
    {
        $this->user = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withId(666)->build();

        $tracker = TrackerTestBuilder::aTracker()->withUserCanView(true)->build();

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->with(3)->willReturn($tracker);

        $report = $this->createMock(\Tracker_Report::class);
        $report->method('getTracker')->willReturn($tracker);

        $report_factory = $this->createMock(\Tracker_ReportFactory::class);
        $report_factory->method('getReportById')->with('2', $this->user->getId(), true)->willReturn($report);

        $form_element = DateFieldBuilder::aDateField(4)->inTracker($tracker)->build();

        $form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $form_element_factory->method('getFormElementById')->with($form_element->getId())->willReturn($form_element);

        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();

        $artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);
        $artifact_factory->method('getArtifactById')->with(1)->willReturn($artifact);

        $this->url = $this->createPartialMock(\Tracker_URL::class, [
            'getTrackerFactory',
            'getTracker_FormElementFactory',
            'getArtifactFactory',
            'getArtifactReportFactory',
        ]);
        $this->url->method('getTrackerFactory')->willReturn($tracker_factory);
        $this->url->method('getTracker_FormElementFactory')->willReturn($form_element_factory);
        $this->url->method('getArtifactFactory')->willReturn($artifact_factory);
        $this->url->method('getArtifactReportFactory')->willReturn($report_factory);
    }

    public function testGetArtifact(): void
    {
        $request_artifact = HTTPRequestBuilder::get()
            ->withParams([
                'aid' => '1',
                'report' => '2',
                'tracker' => '3',
                'formElement' => '4',
                'group_id' => '5',
            ])->build();

        $this->assertInstanceOf(
            Artifact::class,
            $this->url->getDispatchableFromRequest($request_artifact, $this->user),
        );
    }

    public function testGetReport(): void
    {
        $request_artifact = HTTPRequestBuilder::get()
            ->withParams([
                'report' => '2',
                'tracker' => '3',
                'formElement' => '4',
                'group_id' => '5',
            ])->build();

        $this->assertInstanceOf(
            Tracker_Report::class,
            $this->url->getDispatchableFromRequest($request_artifact, $this->user),
        );
    }

    public function testGetTracker(): void
    {
        $request_artifact = HTTPRequestBuilder::get()
            ->withParams([
                'tracker' => '3',
                'formElement' => '4',
                'group_id' => '5',
            ])->build();

        $this->assertInstanceOf(
            Tracker::class,
            $this->url->getDispatchableFromRequest($request_artifact, $this->user)
        );
    }

    public function testGetTrackerWithAtid(): void
    {
        $request_artifact = HTTPRequestBuilder::get()
            ->withParams([
                'atid' => '3',
                'formElement' => '4',
                'group_id' => '5',
            ])->build();

        $this->assertInstanceOf(
            Tracker::class,
            $this->url->getDispatchableFromRequest($request_artifact, $this->user)
        );
    }

    public function testGetField(): void
    {
        $request_artifact = HTTPRequestBuilder::get()
            ->withParams([
                'formElement' => '4',
                'group_id' => '5',
            ])->build();

        $this->assertInstanceOf(
            Tracker_FormElement_Interface::class,
            $this->url->getDispatchableFromRequest($request_artifact, $this->user),
        );
    }

    public function testGetNotMatchingElement(): void
    {
        $request_artifact = HTTPRequestBuilder::get()
            ->withParams([
                'group_id' => '5',
            ])->build();


        $this->expectException(Tracker_NoMachingResourceException::class);

        $this->url->getDispatchableFromRequest($request_artifact, $this->user);
    }
}
