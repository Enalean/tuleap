<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\User\SVNToken;

use CSRFSynchronizerToken;
use Feedback;
use PHPUnit\Framework\MockObject\MockObject;
use SVN_TokenHandler;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder;

final class SVNTokenRevokeControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&CSRFSynchronizerToken $csrf_token;
    private MockObject&SVN_TokenHandler $svn_token_handler;
    private SVNTokenRevokeController $controller;

    protected function setUp(): void
    {
        $this->csrf_token        = $this->createMock(CSRFSynchronizerToken::class);
        $this->svn_token_handler = $this->createMock(SVN_TokenHandler::class);

        $this->controller = new SVNTokenRevokeController($this->csrf_token, $this->svn_token_handler);
    }

    protected function tearDown(): void
    {
        unset($_SESSION);
    }

    public function testItForbidsAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItChecksCSRFToken(): void
    {
        $this->csrf_token->expects(self::once())->method('check')->with('/account/keys-tokens');
        $this->svn_token_handler->method('deleteSVNTokensForUser');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withUser(UserTestBuilder::aUser()->withId(120)->build())
                ->withParam('svn-tokens-selected', [])->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDeletesSelectedToken(): void
    {
        $user = UserTestBuilder::aUser()->withId(120)->build();
        $this->csrf_token->method('check');

        $this->svn_token_handler->expects(self::once())->method('deleteSVNTokensForUser')->with($user, ['2', '5']);

        $layout_inspector = new LayoutInspector();
        $redirect_url     = null;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($user)->withParam('svn-tokens-selected', ['2', '5'])->build(),
                LayoutBuilder::buildWithInspector($layout_inspector),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $redirect_url = $ex->redirect_url;
        }

        $feedback = $layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(Feedback::INFO, $feedback[0]['level']);
        self::assertEquals('/account/keys-tokens', $redirect_url);
    }
}
