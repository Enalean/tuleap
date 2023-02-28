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

declare(strict_types=1);

namespace Tuleap\GitLFS\Transfer;

use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationException;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationVerifier;
use Tuleap\GitLFS\Authorization\Action\AuthorizedAction;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationType;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class LFSActionUserAccessHTTPRequestCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \gitlfsPlugin&\PHPUnit\Framework\MockObject\Stub $plugin;
    private SplitTokenIdentifierTranslator&\PHPUnit\Framework\MockObject\Stub $authorization_token_unserializer;
    private \PHPUnit\Framework\MockObject\Stub&ActionAuthorizationVerifier $authorization_verifier;

    protected function setUp(): void
    {
        $this->plugin                           = $this->createStub(\gitlfsPlugin::class);
        $this->authorization_token_unserializer = $this->createStub(SplitTokenIdentifierTranslator::class);
        $this->authorization_verifier           = $this->createStub(ActionAuthorizationVerifier::class);
    }

    public function testUserWithValidAuthorizationCanAccess(): void
    {
        $this->plugin->method('isAllowed')->willReturn(true);
        $this->authorization_token_unserializer->method('getSplitToken')
            ->willReturn($this->createStub(SplitToken::class));
        $project    = ProjectTestBuilder::aProject()->withId(101)->build();
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getProject')->willReturn($project);
        $repository->method('isCreated')->willReturn(true);
        $authorized_action = $this->createStub(AuthorizedAction::class);
        $authorized_action->method('getRepository')->willReturn($repository);
        $this->authorization_verifier->method('getAuthorization')->willReturn($authorized_action);

        $access_checker = new LFSActionUserAccessHTTPRequestChecker(
            $this->plugin,
            $this->authorization_token_unserializer,
            $this->authorization_verifier
        );

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_AUTHORIZATION')
            ->willReturn('valid_auth');

        $authorized_action = $access_checker->userCanAccess(
            $request,
            $this->createStub(ActionAuthorizationType::class),
            'oid'
        );
        $this->assertInstanceOf(AuthorizedAction::class, $authorized_action);
    }

    public function testRequestWithoutAuthorizationIsDenied(): void
    {
        $access_checker = new LFSActionUserAccessHTTPRequestChecker(
            $this->plugin,
            $this->authorization_token_unserializer,
            $this->authorization_verifier
        );

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_AUTHORIZATION')
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $access_checker->userCanAccess(
            $request,
            $this->createStub(ActionAuthorizationType::class),
            'oid'
        );
    }

    public function testRequestWithAnIncorrectlyFormattedAuthorizationIsDenied(): void
    {
        $this->authorization_token_unserializer->method('getSplitToken')
            ->willThrowException(new InvalidIdentifierFormatException());

        $access_checker = new LFSActionUserAccessHTTPRequestChecker(
            $this->plugin,
            $this->authorization_token_unserializer,
            $this->authorization_verifier
        );

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_AUTHORIZATION')
            ->willReturn('incorrectly_formatted_header');

        $this->expectException(ForbiddenException::class);

        $access_checker->userCanAccess(
            $request,
            $this->createStub(ActionAuthorizationType::class),
            'oid'
        );
    }

    public function testRequestWithAnInvalidAuthorizationIsDenied(): void
    {
        $this->authorization_token_unserializer->method('getSplitToken')
            ->willReturn($this->createStub(SplitToken::class));
        $authorization_expection = $this->createStub(ActionAuthorizationException::class);
        $this->authorization_verifier->method('getAuthorization')->willThrowException($authorization_expection);

        $access_checker = new LFSActionUserAccessHTTPRequestChecker(
            $this->plugin,
            $this->authorization_token_unserializer,
            $this->authorization_verifier
        );

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_AUTHORIZATION')
            ->willReturn('invalid_authorization');

        $this->expectException(ForbiddenException::class);

        $access_checker->userCanAccess(
            $request,
            $this->createStub(ActionAuthorizationType::class),
            'oid'
        );
    }

    public function testRequestAboutANotAccessibleRepositoryIsRejected(): void
    {
        $this->authorization_token_unserializer->method('getSplitToken')
            ->willReturn($this->createStub(SplitToken::class));
        $authorized_action = $this->createStub(AuthorizedAction::class);
        $authorized_action->method('getRepository')->willReturn(null);
        $this->authorization_verifier->method('getAuthorization')->willReturn($authorized_action);

        $access_checker = new LFSActionUserAccessHTTPRequestChecker(
            $this->plugin,
            $this->authorization_token_unserializer,
            $this->authorization_verifier
        );

        $request = $this->createStub(\HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_AUTHORIZATION')
            ->willReturn('valid_auth');

        $this->expectException(NotFoundException::class);

        $access_checker->userCanAccess(
            $request,
            $this->createStub(ActionAuthorizationType::class),
            'oid'
        );
    }
}
