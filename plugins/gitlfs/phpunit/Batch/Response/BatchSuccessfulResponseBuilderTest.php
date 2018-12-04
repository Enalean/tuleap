<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationTokenCreator;
use Tuleap\GitLFS\Batch\Request\BatchRequestOperation;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectID;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;

class BatchSuccessfulResponseBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $token_creator;
    private $token_formatter;
    private $object_retriever;
    private $logger;

    protected function setUp()
    {
        $this->token_creator    = \Mockery::mock(ActionAuthorizationTokenCreator::class);
        $this->token_formatter  = \Mockery::mock(SplitTokenFormatter::class);
        $this->object_retriever = \Mockery::mock(LFSObjectRetriever::class);
        $this->logger           = \Mockery::mock(\Logger::class);
    }

    public function testResponseForUploadRequestIsBuilt()
    {
        $this->token_creator->shouldReceive('createActionAuthorizationToken')->andReturns(\Mockery::mock(SplitToken::class));
        $this->logger->shouldReceive('debug');

        $current_time = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
        $repository   = \Mockery::mock(\GitRepository::class);
        $operation    = \Mockery::mock(BatchRequestOperation::class);
        $operation->shouldReceive('isUpload')->andReturns(true);
        $operation->shouldReceive('isDownload')->andReturns(false);

        $request_new_object = \Mockery::mock(LFSObject::class);
        $request_new_object->shouldReceive('getOID')->andReturns(\Mockery::spy(LFSObjectID::class));
        $request_new_object->shouldReceive('getSize')->andReturns(123456);
        $request_existing_object = \Mockery::mock(LFSObject::class);
        $request_existing_object->shouldReceive('getOID')->andReturns(\Mockery::spy(LFSObjectID::class));
        $request_existing_object->shouldReceive('getSize')->andReturns(456789);

        $this->object_retriever->shouldReceive('getExistingLFSObjectsFromTheSetForRepository')->andReturns([$request_existing_object]);

        $builder        = new BatchSuccessfulResponseBuilder(
            $this->token_creator,
            $this->token_formatter,
            $this->object_retriever,
            $this->logger
        );
        $batch_response = $builder->build(
            $current_time,
            'https://example.com',
            $repository,
            $operation,
            $request_new_object,
            $request_existing_object
        );

        $this->assertInstanceOf(BatchSuccessfulResponse::class, $batch_response);
    }

    public function testResponseForDownloadRequestIsBuilt()
    {
        $this->token_creator->shouldReceive('createActionAuthorizationToken')->andReturns(\Mockery::mock(SplitToken::class));
        $this->logger->shouldReceive('debug');

        $current_time = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
        $repository   = \Mockery::mock(\GitRepository::class);
        $operation    = \Mockery::mock(BatchRequestOperation::class);
        $operation->shouldReceive('isUpload')->andReturns(false);
        $operation->shouldReceive('isDownload')->andReturns(true);

        $request_object1 = \Mockery::mock(LFSObject::class);
        $request_object1->shouldReceive('getOID')->andReturns(\Mockery::spy(LFSObjectID::class));
        $request_object1->shouldReceive('getSize')->andReturns(123456);
        $request_object2 = \Mockery::mock(LFSObject::class);
        $request_object2->shouldReceive('getOID')->andReturns(\Mockery::spy(LFSObjectID::class));
        $request_object2->shouldReceive('getSize')->andReturns(654321);

        $this->object_retriever->shouldReceive('getExistingLFSObjectsFromTheSetForRepository')->andReturns([$request_object2]);

        $builder        = new BatchSuccessfulResponseBuilder(
            $this->token_creator,
            $this->token_formatter,
            $this->object_retriever,
            $this->logger
        );
        $batch_response = $builder->build(
            $current_time,
            'https://example.com',
            $repository,
            $operation,
            $request_object1,
            $request_object2
        );

        $this->assertInstanceOf(BatchSuccessfulResponse::class, $batch_response);
    }

    /**
     * @expectedException \Tuleap\GitLFS\Batch\Response\UnknownOperationException
     */
    public function testBuildingResponseForAnUnknownResponseIsRejected()
    {
        $current_time = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
        $repository   = \Mockery::mock(\GitRepository::class);
        $operation    = \Mockery::mock(BatchRequestOperation::class);
        $operation->shouldReceive('isUpload')->andReturns(false);
        $operation->shouldReceive('isDownload')->andReturns(false);

        $builder = new BatchSuccessfulResponseBuilder(
            $this->token_creator,
            $this->token_formatter,
            $this->object_retriever,
            $this->logger
        );

        $builder->build(
            $current_time,
            'https://example.com',
            $repository,
            $operation
        );
    }
}
