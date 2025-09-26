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
use Git_Exec;
use GitRepository;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LogAnalyzerTest extends TestCase
{
    private Git_Exec&MockObject $git_exec;
    private PFUser $user;
    private GitRepository $repository;
    private TestLogger $logger;
    private LogAnalyzer $log_analyzer;

    #[\Override]
    protected function setUp(): void
    {
        $this->git_exec   = $this->createMock(Git_Exec::class);
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->build();
        $this->user       = UserTestBuilder::buildWithDefaults();
        $this->logger     = new TestLogger();

        $this->log_analyzer = new LogAnalyzer($this->git_exec, $this->logger);
    }

    public function testItUpdatesBranch(): void
    {
        $this->git_exec->expects($this->once())->method('revListInChronologicalOrder')->with('d8f1e57', '469eaa9')->willReturn(['469eaa9']);
        $this->git_exec->method('getObjectType');

        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            'd8f1e57',
            '469eaa9',
            'refs/heads/master'
        );
        self::assertEquals(PushDetails::ACTION_UPDATE, $push_details->getType());
        self::assertEquals(['469eaa9'], $push_details->getRevisionList());
        self::assertEquals('refs/heads/master', $push_details->getRefname());
    }

    public function testItCreatesBranch(): void
    {
        $this->git_exec->expects($this->once())->method('revListSinceStart')->with('refs/heads/master', '469eaa9')->willReturn(['469eaa9']);
        $this->git_exec->method('getObjectType');

        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            LogAnalyzer::FAKE_EMPTY_COMMIT,
            '469eaa9',
            'refs/heads/master'
        );
        self::assertEquals(PushDetails::ACTION_CREATE, $push_details->getType());
        self::assertEquals(['469eaa9'], $push_details->getRevisionList());
        self::assertEquals('refs/heads/master', $push_details->getRefname());
    }

    public function testItDeletesBranch(): void
    {
        $this->git_exec->method('getObjectType');
        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            '469eaa9',
            LogAnalyzer::FAKE_EMPTY_COMMIT,
            'refs/heads/master'
        );
        self::assertEquals(PushDetails::ACTION_DELETE, $push_details->getType());
        self::assertEquals([], $push_details->getRevisionList());
        self::assertEquals('refs/heads/master', $push_details->getRefname());
    }

    public function testItTakesNewRevHashToIdentifyRevTypeOnUpdate(): void
    {
        $this->git_exec->method('revListInChronologicalOrder')->willReturn(['469eaa9']);
        $this->git_exec->expects($this->once())->method('getObjectType')->with('469eaa9')->willReturn(
            PushDetails::OBJECT_TYPE_COMMIT
        );

        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            'd8f1e57',
            '469eaa9',
            'refs/heads/master'
        );
        self::assertEquals(PushDetails::OBJECT_TYPE_COMMIT, $push_details->getRevType());
    }

    public function testItTakesNewRevHashToIdentifyRevTypeOnCreate(): void
    {
        $this->git_exec->method('revListSinceStart')->willReturn(['469eaa9']);
        $this->git_exec->expects($this->once())->method('getObjectType')->with('469eaa9')->willReturn(
            PushDetails::OBJECT_TYPE_COMMIT
        );

        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            LogAnalyzer::FAKE_EMPTY_COMMIT,
            '469eaa9',
            'refs/heads/master'
        );
        self::assertEquals(PushDetails::OBJECT_TYPE_COMMIT, $push_details->getRevType());
    }

    public function testItTakesOldRevHashToIdentifyRevTypeOnDelete(): void
    {
        $this->git_exec->expects($this->once())->method('getObjectType')->with('469eaa9')->willReturn(
            PushDetails::OBJECT_TYPE_COMMIT
        );

        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            '469eaa9',
            LogAnalyzer::FAKE_EMPTY_COMMIT,
            'refs/heads/master'
        );
        self::assertEquals(PushDetails::OBJECT_TYPE_COMMIT, $push_details->getRevType());
    }

    public function testItCommitsOnWorkingBranch(): void
    {
        $this->git_exec->method('revListInChronologicalOrder')->willReturn([]);
        $this->git_exec->method('getObjectType')->willReturn(PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            'd8f1e57',
            '469eaa9',
            'refs/heads/master'
        );
        self::assertEquals(PushDetails::TYPE_BRANCH, $push_details->getRefnameType());
    }

    public function testItCommitsAnUnannotatedTag(): void
    {
        $this->git_exec->method('revListInChronologicalOrder')->willReturn([]);
        $this->git_exec->method('getObjectType')->willReturn(PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            'd8f1e57',
            '469eaa9',
            'refs/tags/v1.0'
        );
        self::assertEquals(PushDetails::TYPE_UNANNOTATED_TAG, $push_details->getRefnameType());
    }

    public function testItCommitsAnAnnotatedTag(): void
    {
        $this->git_exec->method('revListInChronologicalOrder')->willReturn([]);
        $this->git_exec->method('getObjectType')->willReturn(PushDetails::OBJECT_TYPE_TAG);

        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            'd8f1e57',
            '469eaa9',
            'refs/tags/v1.0'
        );
        self::assertEquals(PushDetails::TYPE_ANNOTATED_TAG, $push_details->getRefnameType());
    }

    public function testItCommitsOnRemoteTrackingBranch(): void
    {
        $this->git_exec->method('revListInChronologicalOrder')->willReturn([]);
        $this->git_exec->method('getObjectType')->willReturn(PushDetails::OBJECT_TYPE_COMMIT);

        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            'd8f1e57',
            '469eaa9',
            'refs/remotes/bla'
        );
        self::assertEquals(PushDetails::TYPE_TRACKING_BRANCH, $push_details->getRefnameType());
    }

    public function testItGeneratesAnEmptyPushDetailWhenCannotExtractRevList(): void
    {
        $this->git_exec->method('revListInChronologicalOrder')->willThrowException(new Git_Command_Exception('cmd', ['stuff'], '233'));

        $push_details = $this->log_analyzer->getPushDetails(
            $this->repository,
            $this->user,
            'd8f1e57',
            '469eaa9',
            'refs/remotes/bla'
        );
        self::assertEquals(PushDetails::ACTION_ERROR, $push_details->getType());
        self::assertEquals([], $push_details->getRevisionList());
        self::assertTrue($this->logger->hasErrorRecords());
    }
}
