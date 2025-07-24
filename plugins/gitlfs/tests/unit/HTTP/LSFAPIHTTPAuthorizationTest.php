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

declare(strict_types=1);

namespace Tuleap\GitLFS\HTTP;

use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperationDownload;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperationUpload;
use Tuleap\GitLFS\Authorization\User\UserAuthorizationException;
use Tuleap\GitLFS\Authorization\User\UserTokenVerifier;
use Tuleap\GitLFS\Batch\Request\BatchRequest;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LSFAPIHTTPAuthorizationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UserTokenVerifier&\PHPUnit\Framework\MockObject\MockObject $token_verifier;
    private SplitTokenIdentifierTranslator&\PHPUnit\Framework\MockObject\Stub $token_unserializer;

    #[\Override]
    protected function setUp(): void
    {
        $this->token_verifier     = $this->createMock(UserTokenVerifier::class);
        $this->token_unserializer = $this->createStub(SplitTokenIdentifierTranslator::class);
    }

    public function testUserWithAValidAuthorizationTokenIsFoundWithADownloadOperation(): void
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->willReturn('valid_authorization');
        $this->token_unserializer->method('getSplitToken')->willReturn($this->createStub(SplitToken::class));
        $batch_request = $this->createStub(BatchRequest::class);
        $batch_request->method('isRead')->willReturn(true);
        $batch_request->method('isWrite')->willReturn(false);
        $expected_user = UserTestBuilder::aUser()->build();
        $this->token_verifier->method('getUser')->willReturn($expected_user);

        self::assertSame(
            $expected_user,
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                $this->createStub(\GitRepository::class),
                $batch_request
            )
        );
    }

    public function testUserWithAValidAuthorizationTokenIsFoundWithAnUploadOperation(): void
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->willReturn('valid_authorization');
        $this->token_unserializer->method('getSplitToken')->willReturn($this->createStub(SplitToken::class));
        $batch_request = $this->createStub(BatchRequest::class);
        $batch_request->method('isRead')->willReturn(false);
        $batch_request->method('isWrite')->willReturn(true);
        $expected_user = UserTestBuilder::aUser()->build();
        $this->token_verifier->method('getUser')->willReturn($expected_user);

        self::assertSame(
            $expected_user,
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                $this->createStub(\GitRepository::class),
                $batch_request
            )
        );
    }

    public function testUserIsFoundWhenTheOperationCanBeEitherAnDownloadOrUploadOperationAndUploadTokenIsGiven(): void
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->willReturn('valid_authorization');
        $this->token_unserializer->method('getSplitToken')->willReturn($this->createStub(SplitToken::class));
        $lfs_request = $this->createStub(GitLfsHTTPOperation::class);
        $lfs_request->method('isRead')->willReturn(true);
        $lfs_request->method('isWrite')->willReturn(true);
        $expected_user = UserTestBuilder::aUser()->build();
        $this->token_verifier->method('getUser')->willReturnCallback(
            fn (mixed $_1, mixed $_2, mixed $_3, UserOperation $user_operation): ?\PFUser => match ($user_operation::class) {
                UserOperationDownload::class => null,
                UserOperationUpload::class => $expected_user,
            }
        );

        self::assertSame(
            $expected_user,
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                $this->createStub(\GitRepository::class),
                $lfs_request
            )
        );
    }

    public function testNoUserIsRetrievedWhenThereIsNoAuthorizationHeader(): void
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->willReturn(false);

        $this->assertNull(
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                $this->createStub(\GitRepository::class),
                $this->createStub(BatchRequest::class)
            )
        );
    }

    public function testNoUserIsRetrievedWhenTheAuthorizationHeaderIsNotAValidToken(): void
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->willReturn('invalid_token');
        $this->token_unserializer->method('getSplitToken')->willThrowException(new InvalidIdentifierFormatException());

        $this->assertNull(
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                $this->createStub(\GitRepository::class),
                $this->createStub(BatchRequest::class)
            )
        );
    }

    public function testNoUserIsRetrievedWhenTheBatchRequestOperationIsNotKnown(): void
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->willReturn('valid_token');
        $this->token_unserializer->method('getSplitToken')->willReturn($this->createStub(SplitToken::class));
        $batch_request = $this->createStub(BatchRequest::class);
        $batch_request->method('isRead')->willReturn(false);
        $batch_request->method('isWrite')->willReturn(false);

        $this->assertNull(
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                $this->createStub(\GitRepository::class),
                $batch_request
            )
        );
    }

    public function testNoUserIsRetrievedWhenTheAuthorizationIsNotValid(): void
    {
        $lfs_http_api_authorization = new LSFAPIHTTPAuthorization($this->token_verifier, $this->token_unserializer);

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->willReturn('valid_authorization');
        $this->token_unserializer->method('getSplitToken')->willReturn($this->createStub(SplitToken::class));
        $batch_request = $this->createStub(BatchRequest::class);
        $batch_request->method('isRead')->willReturn(true);
        $batch_request->method('isWrite')->willReturn(false);
        $this->token_verifier->method('getUser')->willThrowException($this->createStub(UserAuthorizationException::class));

        $this->assertNull(
            $lfs_http_api_authorization->getUserFromAuthorizationToken(
                $request,
                $this->createStub(\GitRepository::class),
                $batch_request
            )
        );
    }
}
