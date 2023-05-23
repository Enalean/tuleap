<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Batch\Response;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\GitLFS\Admin\AdminDao;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationTokenCreator;
use Tuleap\GitLFS\Batch\Request\BatchRequestOperation;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectID;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Project\Quota\ProjectQuotaChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class BatchSuccessfulResponseBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ActionAuthorizationTokenCreator&\PHPUnit\Framework\MockObject\Stub $token_creator;
    private SplitTokenFormatter&\PHPUnit\Framework\MockObject\Stub $token_formatter;
    private LFSObjectRetriever&\PHPUnit\Framework\MockObject\Stub $object_retriever;
    private AdminDao&\PHPUnit\Framework\MockObject\Stub $admin_dao;
    private TestLogger $logger;
    private Prometheus&\PHPUnit\Framework\MockObject\MockObject $prometheus;
    private ProjectQuotaChecker&\PHPUnit\Framework\MockObject\Stub $project_quota_checker;
    private \GitRepository&\PHPUnit\Framework\MockObject\Stub $repository;

    protected function setUp(): void
    {
        $this->token_creator         = $this->createStub(ActionAuthorizationTokenCreator::class);
        $this->token_formatter       = $this->createStub(SplitTokenFormatter::class);
        $this->object_retriever      = $this->createStub(LFSObjectRetriever::class);
        $this->admin_dao             = $this->createStub(AdminDao::class);
        $this->logger                = new TestLogger();
        $this->prometheus            = $this->createMock(Prometheus::class);
        $this->project_quota_checker = $this->createStub(ProjectQuotaChecker::class);
        $this->repository            = $this->createStub(\GitRepository::class);

        $project = ProjectTestBuilder::aProject()->withId(102)->build();
        $this->repository->method('getProject')->willReturn($project);
    }

    public function testResponseForUploadRequestIsBuilt(): void
    {
        $this->token_creator->method('createActionAuthorizationToken')->willReturn($this->createStub(SplitToken::class));
        $this->prometheus->expects(self::once())->method('increment');

        $current_time = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
        $operation    = $this->createStub(BatchRequestOperation::class);
        $operation->method('isUpload')->willReturn(true);
        $operation->method('isDownload')->willReturn(false);

        $request_new_object      = new LFSObject($this->buildLFSObjectID(), 123456);
        $request_existing_object = new LFSObject($this->buildLFSObjectID(), 456789);

        $this->object_retriever->method('getExistingLFSObjectsFromTheSetForRepository')->willReturn([$request_existing_object]);
        $this->project_quota_checker->method('hasEnoughSpaceForProject')->willReturn(true);
        $this->admin_dao->method('getFileMaxSize')->willReturn(536870912);

        $builder        = new BatchSuccessfulResponseBuilder(
            $this->token_creator,
            $this->token_formatter,
            $this->object_retriever,
            $this->admin_dao,
            $this->project_quota_checker,
            $this->logger,
            $this->prometheus
        );
        $batch_response = $builder->build(
            $current_time,
            'https://example.com',
            $this->repository,
            $operation,
            $request_new_object,
            $request_existing_object
        );

        $this->assertInstanceOf(BatchSuccessfulResponse::class, $batch_response);
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testResponseForDownloadRequestIsBuilt(): void
    {
        $this->token_creator->method('createActionAuthorizationToken')->willReturn($this->createStub(SplitToken::class));
        $this->prometheus->expects(self::once())->method('increment');

        $current_time = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
        $operation    = $this->createStub(BatchRequestOperation::class);
        $operation->method('isUpload')->willReturn(false);
        $operation->method('isDownload')->willReturn(true);

        $request_object1 = new LFSObject($this->buildLFSObjectID(), 123456);
        $request_object2 = new LFSObject($this->buildLFSObjectID(), 654321);

        $this->object_retriever->method('getExistingLFSObjectsFromTheSetForRepository')->willReturn([$request_object2]);
        $this->project_quota_checker->method('hasEnoughSpaceForProject')->willReturn(true);
        $this->admin_dao->method('getFileMaxSize')->willReturn(536870912);

        $builder        = new BatchSuccessfulResponseBuilder(
            $this->token_creator,
            $this->token_formatter,
            $this->object_retriever,
            $this->admin_dao,
            $this->project_quota_checker,
            $this->logger,
            $this->prometheus
        );
        $batch_response = $builder->build(
            $current_time,
            'https://example.com',
            $this->repository,
            $operation,
            $request_object1,
            $request_object2
        );

        $this->assertInstanceOf(BatchSuccessfulResponse::class, $batch_response);
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testBuildingResponseForAnUnknownResponseIsRejected(): void
    {
        $current_time = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
        $operation    = $this->createStub(BatchRequestOperation::class);
        $operation->method('isUpload')->willReturn(false);
        $operation->method('isDownload')->willReturn(false);

        $this->project_quota_checker->method('hasEnoughSpaceForProject')->willReturn(true);
        $this->admin_dao->method('getFileMaxSize')->willReturn(536870912);

        $builder = new BatchSuccessfulResponseBuilder(
            $this->token_creator,
            $this->token_formatter,
            $this->object_retriever,
            $this->admin_dao,
            $this->project_quota_checker,
            $this->logger,
            $this->prometheus
        );

        $this->expectException(UnknownOperationException::class);

        $builder->build(
            $current_time,
            'https://example.com',
            $this->repository,
            $operation
        );
    }

    public function testBuildingResponseWithAFileWithASizeBiggerThanMaxSizeIsRejected(): void
    {
        $this->token_creator->method('createActionAuthorizationToken')->willReturn($this->createStub(SplitToken::class));
        $this->prometheus->expects(self::once())->method('increment');

        $current_time = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
        $operation    = new BatchRequestOperation(BatchRequestOperation::UPLOAD_OPERATION);

        $request_object1 = new LFSObject($this->buildLFSObjectID(), 1);
        $request_object2 = new LFSObject($this->buildLFSObjectID(), 654321);

        $this->object_retriever->method('getExistingLFSObjectsFromTheSetForRepository')->willReturn([]);
        $this->project_quota_checker->method('hasEnoughSpaceForProject')->willReturn(true);
        $this->admin_dao->method('getFileMaxSize')->willReturn(1);

        $builder = new BatchSuccessfulResponseBuilder(
            $this->token_creator,
            $this->token_formatter,
            $this->object_retriever,
            $this->admin_dao,
            $this->project_quota_checker,
            $this->logger,
            $this->prometheus
        );

        $this->expectException(MaxFileSizeException::class);

        $builder->build(
            $current_time,
            'https://example.com',
            $this->repository,
            $operation,
            $request_object1,
            $request_object2
        );
    }

    public function testBuildingResponseWithAFileWithASizeBiggerThanProjectQuotaIsRejected(): void
    {
        $this->token_creator->method('createActionAuthorizationToken')->willReturn($this->createStub(SplitToken::class));

        $current_time = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
        $operation    = new BatchRequestOperation(BatchRequestOperation::UPLOAD_OPERATION);

        $request_object1 = new LFSObject($this->buildLFSObjectID(), 1);
        $request_object2 = new LFSObject($this->buildLFSObjectID(), 654321);

        $this->object_retriever->method('getExistingLFSObjectsFromTheSetForRepository')->willReturn([]);
        $this->project_quota_checker->method('hasEnoughSpaceForProject')->willReturn(false);
        $this->admin_dao->method('getFileMaxSize')->willReturn(536870912);

        $builder = new BatchSuccessfulResponseBuilder(
            $this->token_creator,
            $this->token_formatter,
            $this->object_retriever,
            $this->admin_dao,
            $this->project_quota_checker,
            $this->logger,
            $this->prometheus
        );

        $this->expectException(ProjectQuotaExceededException::class);

        $builder->build(
            $current_time,
            'https://example.com',
            $this->repository,
            $operation,
            $request_object1,
            $request_object2
        );
    }

    private function buildLFSObjectID(): LFSObjectID
    {
        return new LFSObjectID(str_repeat('a', 64));
    }
}
