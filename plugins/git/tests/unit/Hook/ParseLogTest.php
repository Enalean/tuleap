<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook;

use ColinODell\PsrTestLogger\TestLogger;

final class ParseLogTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface & CrossReferencesExtractor
     */
    private $extract_cross_ref;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface & LogPushes
     */
    private $log_pushes;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->extract_cross_ref = \Mockery::spy(CrossReferencesExtractor::class);
        $this->log_pushes        = \Mockery::spy(\Tuleap\Git\Hook\LogPushes::class);
        $this->logger            = new TestLogger();
    }

    private function executeParseLog(PushDetails $push_details): void
    {
        $parse_log = new ParseLog($this->log_pushes, $this->extract_cross_ref, $this->logger);
        $parse_log->execute($push_details);
    }

    public function testItLogPush(): void
    {
        $push_details = \Mockery::spy(PushDetails::class)
            ->shouldReceive('getRevisionList')
            ->andReturns(['469eaa9'])
            ->getMock();

        $this->log_pushes->shouldReceive('executeForRepository')->with($push_details)->once();

        $this->executeParseLog($push_details);
    }

    public function testItExecutesExtractOnEachCommit(): void
    {
        $push_details = \Mockery::spy(PushDetails::class)
            ->shouldReceive('getRevisionList')
            ->andReturns(['469eaa9', '5eb01f0'])
            ->getMock();

        $this->extract_cross_ref->shouldReceive('extractCommitReference')->twice();

        $this->executeParseLog($push_details);
    }

    public function testItExecutesExtractOnTag(): void
    {
        $push_details = \Mockery::spy(PushDetails::class)
            ->shouldReceive('getRevisionList')
            ->andReturns(['469eaa9'])
            ->getMock();

        $this->extract_cross_ref->shouldReceive('extractCommitReference')->with($push_details, '469eaa9')->once();

        $this->executeParseLog($push_details);
    }

    public function testItDoesntAttemptToExtractWhenBranchIsDeleted(): void
    {
        $push_details = new PushDetails(
            \Mockery::mock(\GitRepository::class),
            \Tuleap\Test\Builders\UserTestBuilder::anActiveUser()->build(),
            'refs/tags/v1',
            'create',
            'tag',
            []
        );

        $this->log_pushes->shouldReceive('executeForRepository')
            ->once()
            ->with($push_details);

        $this->extract_cross_ref->shouldReceive('extractTagReference')->once();

        $this->executeParseLog($push_details);
    }

    public function testItExecutesExtractEvenWhenThereAreErrors(): void
    {
        $push_details = \Mockery::spy(PushDetails::class);
        $push_details->shouldReceive('getRevisionList')->andReturns(['0fb0737', '469eaa9']);
        $push_details->shouldReceive('getRepository')->andReturns(\Mockery::spy(\GitRepository::class));

        $this->extract_cross_ref->shouldReceive('extractCommitReference')->with($push_details, '0fb0737');
        $this->extract_cross_ref->shouldReceive('extractCommitReference')
            ->with($push_details, '469eaa9')
            ->andThrows(new \Git_Command_Exception('whatever', ['whatever'], '234'));

        $this->executeParseLog($push_details);

        self::assertTrue($this->logger->hasErrorRecords());
    }
}
