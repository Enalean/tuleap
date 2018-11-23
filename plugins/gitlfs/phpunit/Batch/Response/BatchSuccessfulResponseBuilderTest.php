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
use Tuleap\GitLFS\Batch\Request\BatchRequestObject;
use Tuleap\GitLFS\Batch\Request\BatchRequestOperation;

class BatchSuccessfulResponseBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $token_creator;
    private $token_formatter;
    private $logger;

    protected function setUp()
    {
        $this->logger = \Mockery::mock(\Logger::class);
    }

    public function testResponseIsBuilt()
    {
        $this->logger->shouldReceive('debug');

        $operation = \Mockery::mock(BatchRequestOperation::class);
        $operation->shouldReceive('isUpload')->andReturns(true);

        $request_object = \Mockery::mock(BatchRequestObject::class);
        $request_object->shouldReceive('getOID')->andReturns('oid');
        $request_object->shouldReceive('getSize')->andReturns(123456);

        $builder        = new BatchSuccessfulResponseBuilder($this->logger);
        $batch_response = $builder->build(
            'https://example.com',
            $operation,
            $request_object
        );

        $this->assertInstanceOf(BatchSuccessfulResponse::class, $batch_response);
    }

    /**
     * @expectedException \Tuleap\GitLFS\Batch\Response\UnknownOperationException
     */
    public function testBuildingResponseForAnUnknownResponseIsRejected()
    {
        $operation    = \Mockery::mock(BatchRequestOperation::class);
        $operation->shouldReceive('isUpload')->andReturns(false);

        $builder = new BatchSuccessfulResponseBuilder($this->logger);

        $builder->build(
            'https://example.com',
            $operation
        );
    }
}
