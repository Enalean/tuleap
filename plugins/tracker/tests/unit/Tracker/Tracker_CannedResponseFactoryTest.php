<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All rights reserved
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

namespace Tuleap\Tracker;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_CannedResponse;
use Tracker_CannedResponseFactory;
use TrackerFactory;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_CannedResponseFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    //testing CannedResponse import
    public function testImport()
    {
        $xml       = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TestTracker-1.xml'));
        $responses = [];
        foreach ($xml->cannedResponses->cannedResponse as $index => $response) {
            $responses[] = Tracker_CannedResponseFactory::instance()->getInstanceFromXML($response);
        }

        $this->assertEquals('new response', $responses[0]->title);
        $this->assertEquals('this is the message of the new canned response', $responses[0]->body);
    }

    public function testDuplicateWithNoCannedResponses()
    {
        $from_tracker = Mockery::mock(Tracker::class);
        $to_tracker   = Mockery::mock(Tracker::class);
        $tf           = Mockery::mock(TrackerFactory::class);
        $tf->shouldReceive('getTrackerById')->with(102)->andReturns($from_tracker);
        $tf->shouldReceive('getTrackerById')->with(502)->andReturns($to_tracker);

        $canned_responses = [];

        $crf = Mockery::mock(Tracker_CannedResponseFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $crf->shouldReceive('getTrackerFactory')->andReturns($tf);
        $crf->shouldReceive('getCannedResponses')->with($from_tracker)->andReturns($canned_responses);
        $crf->shouldReceive('create')->never();
        $crf->duplicate(102, 502);
    }

    public function testDuplicateWithCannedResponses()
    {
        $from_tracker = Mockery::mock(Tracker::class);
        $to_tracker   = Mockery::mock(Tracker::class);
        $tf           = Mockery::mock(TrackerFactory::class);
        $tf->shouldReceive('getTrackerById')->with(102)->andReturns($from_tracker);
        $tf->shouldReceive('getTrackerById')->with(502)->andReturns($to_tracker);

        $cr1 = Mockery::mock(Tracker_CannedResponse::class);
        $cr1->shouldReceive('getTitle')->andReturns('cr1');
        $cr1->shouldReceive('getBody')->andReturns('body of cr1');
        $cr2 = Mockery::mock(Tracker_CannedResponse::class);
        $cr2->shouldReceive('getTitle')->andReturns('cr2');
        $cr2->shouldReceive('getBody')->andReturns('body of cr2');
        $cr3 = Mockery::mock(Tracker_CannedResponse::class);
        $cr3->shouldReceive('getTitle')->andReturns('cr3');
        $cr3->shouldReceive('getBody')->andReturns('body of cr3');
        $crs = [$cr1, $cr2, $cr3];

        $crf = Mockery::mock(Tracker_CannedResponseFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $crf->shouldReceive('getTrackerFactory')->andReturns($tf);
        $crf->shouldReceive('getCannedResponses')->with($from_tracker)->andReturns($crs);
        $crf->shouldReceive('create')->times(3);
        $crf->shouldReceive('create')->with($to_tracker, 'cr1', 'body of cr1')->ordered();
        $crf->shouldReceive('create')->with($to_tracker, 'cr2', 'body of cr2')->ordered();
        $crf->shouldReceive('create')->with($to_tracker, 'cr3', 'body of cr3')->ordered();
        $crf->duplicate(102, 502);
    }
}
