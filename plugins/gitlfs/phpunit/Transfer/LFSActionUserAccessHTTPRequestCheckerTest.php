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

namespace Tuleap\GitLFS\Transfer;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationException;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationVerifier;
use Tuleap\GitLFS\Authorization\Action\AuthorizedAction;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationType;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class LFSActionUserAccessHTTPRequestCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $plugin;
    private $authorization_token_unserializer;
    private $authorization_verifier;

    protected function setUp(): void
    {
        $this->plugin                           = \Mockery::mock(\gitlfsPlugin::class);
        $this->authorization_token_unserializer = \Mockery::mock(SplitTokenIdentifierTranslator::class);
        $this->authorization_verifier           = \Mockery::mock(ActionAuthorizationVerifier::class);
    }

    public function testUserWithValidAuthorizationCanAccess()
    {
        $this->plugin->shouldReceive('isAllowed')->andReturns(true);
        $this->authorization_token_unserializer->shouldReceive('getSplitToken')
            ->andReturns(\Mockery::mock(SplitToken::class));
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns(101);
        $project->shouldReceive('isActive')->andReturns(true);
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getProject')->andReturns($project);
        $repository->shouldReceive('isCreated')->andReturns(true);
        $authorized_action      = \Mockery::mock(AuthorizedAction::class);
        $authorized_action->shouldReceive('getRepository')->andReturns($repository);
        $this->authorization_verifier->shouldReceive('getAuthorization')->andReturns($authorized_action);

        $access_checker = new LFSActionUserAccessHTTPRequestChecker(
            $this->plugin,
            $this->authorization_token_unserializer,
            $this->authorization_verifier
        );

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->with('HTTP_AUTHORIZATION')
            ->andReturns('valid_auth');

        $authorized_action = $access_checker->userCanAccess(
            $request,
            \Mockery::mock(ActionAuthorizationType::class),
            'oid'
        );
        $this->assertInstanceOf(AuthorizedAction::class, $authorized_action);
    }

    public function testRequestWithoutAuthorizationIsDenied()
    {
        $access_checker = new LFSActionUserAccessHTTPRequestChecker(
            $this->plugin,
            $this->authorization_token_unserializer,
            $this->authorization_verifier
        );

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->with('HTTP_AUTHORIZATION')
            ->andReturns(false);

        $this->expectException(ForbiddenException::class);

        $access_checker->userCanAccess(
            $request,
            \Mockery::mock(ActionAuthorizationType::class),
            'oid'
        );
    }

    public function testRequestWithAnIncorrectlyFormattedAuthorizationIsDenied()
    {
        $this->authorization_token_unserializer->shouldReceive('getSplitToken')
            ->andThrow(new InvalidIdentifierFormatException());

        $access_checker = new LFSActionUserAccessHTTPRequestChecker(
            $this->plugin,
            $this->authorization_token_unserializer,
            $this->authorization_verifier
        );

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->with('HTTP_AUTHORIZATION')
            ->andReturns('incorrectly_formatted_header');

        $this->expectException(ForbiddenException::class);

        $access_checker->userCanAccess(
            $request,
            \Mockery::mock(ActionAuthorizationType::class),
            'oid'
        );
    }

    public function testRequestWithAnInvalidAuthorizationIsDenied()
    {
        $this->authorization_token_unserializer->shouldReceive('getSplitToken')
            ->andReturns(\Mockery::mock(SplitToken::class));
        $authorization_expection = \Mockery::mock(ActionAuthorizationException::class);
        $this->authorization_verifier->shouldReceive('getAuthorization')->andThrow($authorization_expection);

        $access_checker = new LFSActionUserAccessHTTPRequestChecker(
            $this->plugin,
            $this->authorization_token_unserializer,
            $this->authorization_verifier
        );

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->with('HTTP_AUTHORIZATION')
            ->andReturns('invalid_authorization');

        $this->expectException(ForbiddenException::class);

        $access_checker->userCanAccess(
            $request,
            \Mockery::mock(ActionAuthorizationType::class),
            'oid'
        );
    }

    public function testRequestAboutANotAccessibleRepositoryIsRejected()
    {
        $this->authorization_token_unserializer->shouldReceive('getSplitToken')
            ->andReturns(\Mockery::mock(SplitToken::class));
        $authorized_action = \Mockery::mock(AuthorizedAction::class);
        $authorized_action->shouldReceive('getRepository')->andReturns(null);
        $this->authorization_verifier->shouldReceive('getAuthorization')->andReturns($authorized_action);

        $access_checker = new LFSActionUserAccessHTTPRequestChecker(
            $this->plugin,
            $this->authorization_token_unserializer,
            $this->authorization_verifier
        );

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getFromServer')->with('HTTP_AUTHORIZATION')
            ->andReturns('valid_auth');

        $this->expectException(NotFoundException::class);

        $access_checker->userCanAccess(
            $request,
            \Mockery::mock(ActionAuthorizationType::class),
            'oid'
        );
    }
}
