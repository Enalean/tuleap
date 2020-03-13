<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\HTTP;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperationDownload;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperationUpload;
use Tuleap\GitLFS\Authorization\User\UserAuthorizationException;
use Tuleap\GitLFS\Authorization\User\UserTokenVerifier;
use Tuleap\GitLFS\Batch\Request\BatchRequest;

final class LSFAPIHTTPAuthorizationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $token_verifier;
    private $token_unserializer;

    protected function setUp() : void
    {
        $this->token_verifier     = \Mockery::mock(UserTokenVerifier::class);
        $this->token_unserializer = \Mockery::mock(SplitTokenIdentifierTranslator::class);
    }

    public function testUserWithAValidAuthorizationTokenIsFoundWithADownloadOperation() : void
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->andReturns('valid_authorization');
        $this->token_unserializer->shouldReceive('getSplitToken')->andReturns(\Mockery::mock(SplitToken::class));
        $batch_request = \Mockery::mock(BatchRequest::class);
        $batch_request->shouldReceive('isRead')->andReturns(true);
        $batch_request->shouldReceive('isWrite')->andReturns(false);
        $expected_user = \Mockery::mock(\PFUser::class);
        $this->token_verifier->shouldReceive('getUser')->andReturns($expected_user);

        $this->assertSame(
            $expected_user,
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                \Mockery::mock(\GitRepository::class),
                $batch_request
            )
        );
    }

    public function testUserWithAValidAuthorizationTokenIsFoundWithAnUploadOperation()
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->andReturns('valid_authorization');
        $this->token_unserializer->shouldReceive('getSplitToken')->andReturns(\Mockery::mock(SplitToken::class));
        $batch_request = \Mockery::mock(BatchRequest::class);
        $batch_request->shouldReceive('isRead')->andReturns(false);
        $batch_request->shouldReceive('isWrite')->andReturns(true);
        $expected_user = \Mockery::mock(\PFUser::class);
        $this->token_verifier->shouldReceive('getUser')->andReturns($expected_user);

        $this->assertSame(
            $expected_user,
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                \Mockery::mock(\GitRepository::class),
                $batch_request
            )
        );
    }

    public function testUserIsFoundWhenTheOperationCanBeEitherAnDownloadOrUploadOperationAndUploadTokenIsGiven() : void
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->andReturns('valid_authorization');
        $this->token_unserializer->shouldReceive('getSplitToken')->andReturns(\Mockery::mock(SplitToken::class));
        $lfs_request = \Mockery::mock(GitLfsHTTPOperation::class);
        $lfs_request->shouldReceive('isRead')->andReturns(true);
        $lfs_request->shouldReceive('isWrite')->andReturns(true);
        $expected_user = \Mockery::mock(\PFUser::class);
        $this->token_verifier->shouldReceive('getUser')
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::type(UserOperationDownload::class))
            ->andReturns(null);
        $this->token_verifier->shouldReceive('getUser')
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::any(), \Mockery::type(UserOperationUpload::class))
            ->andReturns($expected_user);

        $this->assertSame(
            $expected_user,
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                \Mockery::mock(\GitRepository::class),
                $lfs_request
            )
        );
    }

    public function testNoUserIsRetrievedWhenThereIsNoAuthorizationHeader()
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->andReturns(false);

        $this->assertNull(
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                \Mockery::mock(\GitRepository::class),
                \Mockery::mock(BatchRequest::class)
            )
        );
    }

    public function testNoUserIsRetrievedWhenTheAuthorizationHeaderIsNotAValidToken()
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->andReturns('invalid_token');
        $this->token_unserializer->shouldReceive('getSplitToken')->andThrows(InvalidIdentifierFormatException::class);

        $this->assertNull(
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                \Mockery::mock(\GitRepository::class),
                \Mockery::mock(BatchRequest::class)
            )
        );
    }

    public function testNoUserIsRetrievedWhenTheBatchRequestOperationIsNotKnown()
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->andReturns('valid_token');
        $this->token_unserializer->shouldReceive('getSplitToken')->andReturns(\Mockery::mock(SplitToken::class));
        $batch_request = \Mockery::mock(BatchRequest::class);
        $batch_request->shouldReceive('isRead')->andReturns(false);
        $batch_request->shouldReceive('isWrite')->andReturns(false);

        $this->assertNull(
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                \Mockery::mock(\GitRepository::class),
                $batch_request
            )
        );
    }

    public function testNoUserIsRetrievedWhenTheAuthorizationIsNotValid() : void
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->andReturns('valid_authorization');
        $this->token_unserializer->shouldReceive('getSplitToken')->andReturns(\Mockery::mock(SplitToken::class));
        $batch_request = \Mockery::mock(BatchRequest::class);
        $batch_request->shouldReceive('isRead')->andReturns(true);
        $batch_request->shouldReceive('isWrite')->andReturns(false);
        $this->token_verifier->shouldReceive('getUser')->andThrows(\Mockery::mock(UserAuthorizationException::class));

        $this->assertNull(
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                \Mockery::mock(\GitRepository::class),
                $batch_request
            )
        );
    }
}
