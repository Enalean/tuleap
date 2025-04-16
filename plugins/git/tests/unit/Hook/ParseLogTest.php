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

declare(strict_types=1);

namespace Tuleap\Git\Hook;

use ColinODell\PsrTestLogger\TestLogger;
use Git_Command_Exception;
use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ParseLogTest extends TestCase
{
    private CrossReferencesExtractor&MockObject $extract_cross_ref;
    private LogPushes&MockObject $log_pushes;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->extract_cross_ref = $this->createMock(CrossReferencesExtractor::class);
        $this->log_pushes        = $this->createMock(LogPushes::class);
        $this->logger            = new TestLogger();
    }

    private function executeParseLog(PushDetails $push_details): void
    {
        $parse_log = new ParseLog($this->log_pushes, $this->extract_cross_ref, $this->logger);
        $parse_log->execute($push_details);
    }

    public function testItLogPush(): void
    {
        $push_details = $this->createMock(PushDetails::class);
        $push_details->method('getRevisionList')->willReturn(['469eaa9']);
        $push_details->method('getRefnameType');
        $this->extract_cross_ref->method('extractCommitReference');

        $this->log_pushes->expects($this->once())->method('executeForRepository')->with($push_details);

        $this->executeParseLog($push_details);
    }

    public function testItExecutesExtractOnEachCommit(): void
    {
        $push_details = $this->createMock(PushDetails::class);
        $push_details->method('getRevisionList')->willReturn(['469eaa9', '5eb01f0']);
        $push_details->method('getRefnameType');

        $this->extract_cross_ref->expects($this->exactly(2))->method('extractCommitReference');
        $this->log_pushes->method('executeForRepository');

        $this->executeParseLog($push_details);
    }

    public function testItExecutesExtractOnTag(): void
    {
        $push_details = $this->createMock(PushDetails::class);
        $push_details->method('getRevisionList')->willReturn(['469eaa9']);
        $push_details->method('getRefnameType');

        $this->extract_cross_ref->expects($this->once())->method('extractCommitReference')->with($push_details, '469eaa9');
        $this->log_pushes->method('executeForRepository');

        $this->executeParseLog($push_details);
    }

    public function testItDoesntAttemptToExtractWhenBranchIsDeleted(): void
    {
        $push_details = new PushDetails(
            GitRepositoryTestBuilder::aProjectRepository()->build(),
            UserTestBuilder::anActiveUser()->build(),
            'refs/tags/v1',
            'create',
            'tag',
            []
        );

        $this->log_pushes->expects($this->once())->method('executeForRepository')->with($push_details);

        $this->extract_cross_ref->expects($this->once())->method('extractTagReference');

        $this->executeParseLog($push_details);
    }

    public function testItExecutesExtractEvenWhenThereAreErrors(): void
    {
        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method('getFullPath');
        $push_details = $this->createMock(PushDetails::class);
        $push_details->method('getRevisionList')->willReturn(['0fb0737', '469eaa9']);
        $push_details->method('getRepository')->willReturn($git_repository);
        $push_details->method('getRefnameType');
        $push_details->method('getRefname');

        $this->extract_cross_ref->method('extractCommitReference')
            ->willReturnCallback(static function ($details, string $ref) {
                if ($ref === '469eaa9') {
                    throw new Git_Command_Exception('whatever', ['whatever'], '234');
                }
            });

        $this->log_pushes->method('executeForRepository');
        $this->executeParseLog($push_details);

        self::assertTrue($this->logger->hasErrorRecords());
    }
}
