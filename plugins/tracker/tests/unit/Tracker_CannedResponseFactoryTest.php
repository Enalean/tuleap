<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\Tracker;

use Tracker_CannedResponse;
use Tracker_CannedResponseFactory;
use TrackerFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_CannedResponseFactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testImport(): void
    {
        $xml       = simplexml_load_string(file_get_contents(__DIR__ . '/_fixtures/TestTracker-1.xml'));
        $responses = [];
        foreach ($xml->cannedResponses->cannedResponse as $index => $response) {
            $responses[] = Tracker_CannedResponseFactory::instance()->getInstanceFromXML($response);
        }

        $this->assertEquals('new response', $responses[0]->title);
        $this->assertEquals('this is the message of the new canned response', $responses[0]->body);
    }

    public function testDuplicateWithNoCannedResponses(): void
    {
        $from_tracker = $this->createMock(Tracker::class);
        $to_tracker   = $this->createMock(Tracker::class);
        $tf           = $this->createMock(TrackerFactory::class);
        $tf->method('getTrackerById')
            ->willReturnCallback(
                static fn (int $id) => match ($id) {
                    102 => $from_tracker,
                    502 => $to_tracker,
                }
            );

        $canned_responses = [];

        $crf = $this->createPartialMock(Tracker_CannedResponseFactory::class, ['getTrackerFactory', 'getCannedResponses', 'create']);
        $crf->method('getTrackerFactory')->willReturn($tf);
        $crf->method('getCannedResponses')->with($from_tracker)->willReturn($canned_responses);
        $crf->expects($this->never())->method('create');
        $crf->duplicate(102, 502);
    }

    public function testDuplicateWithCannedResponses(): void
    {
        $from_tracker = $this->createMock(Tracker::class);
        $to_tracker   = $this->createMock(Tracker::class);
        $tf           = $this->createMock(TrackerFactory::class);
        $tf->method('getTrackerById')
            ->willReturnCallback(
                static fn (int $id) => match ($id) {
                    102 => $from_tracker,
                    502 => $to_tracker,
                }
            );

        $cr1 = $this->createMock(Tracker_CannedResponse::class);
        $cr1->method('getTitle')->willReturn('cr1');
        $cr1->method('getBody')->willReturn('body of cr1');
        $cr2 = $this->createMock(Tracker_CannedResponse::class);
        $cr2->method('getTitle')->willReturn('cr2');
        $cr2->method('getBody')->willReturn('body of cr2');
        $cr3 = $this->createMock(Tracker_CannedResponse::class);
        $cr3->method('getTitle')->willReturn('cr3');
        $cr3->method('getBody')->willReturn('body of cr3');
        $crs = [$cr1, $cr2, $cr3];

        $crf = $this->createPartialMock(Tracker_CannedResponseFactory::class, ['getTrackerFactory', 'getCannedResponses', 'create']);
        $crf->method('getTrackerFactory')->willReturn($tf);
        $crf->method('getCannedResponses')->with($from_tracker)->willReturn($crs);
        $matcher = $this->exactly(3);
        $crf->expects($matcher)
            ->method('create')
            ->willReturnCallback(
                static fn (Tracker $tracker, string $title, string $body) => match (true) {
                    $matcher->numberOfInvocations() === 1 && $title === 'cr1' && $body === 'body of cr1',
                    $matcher->numberOfInvocations() === 2 && $title === 'cr2' && $body === 'body of cr2',
                    $matcher->numberOfInvocations() === 3 && $title === 'cr3' && $body === 'body of cr3' => true
                }
            );
        $crf->duplicate(102, 502);
    }
}
