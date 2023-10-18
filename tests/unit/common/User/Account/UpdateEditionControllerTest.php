<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;

final class UpdateEditionControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;
    private UpdateEditionController $controller;

    public function setUp(): void
    {
        $this->csrf_token = $this->createMock(CSRFSynchronizerToken::class);

        $this->controller = new UpdateEditionController($this->csrf_token);
    }

    public function testItCannotUpdateWhenUserIsAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesAllPreferences(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getPreference')->willReturnMap([
            ['user_edition_default_format', false],
            ['user_csv_separator', false],
            ['user_csv_dateformat', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $user->method('setPreference')->willReturnMap([
            ['user_edition_default_format', 'html', true],
            ['user_csv_separator', 'comma', true],
            ['user_csv_dateformat', 'day_month_year', true],
        ]);

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('user_text_default_format', 'html')
            ->withParam('user_csv_separator', 'comma')
            ->withParam('user_csv_dateformat', 'day_month_year')
            ->build();

        $layout_inspector = new LayoutInspector();
        $redirect_url     = null;

        try {
            $this->controller->process(
                $request,
                LayoutBuilder::buildWithInspector($layout_inspector),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $redirect_url = $ex->redirect_url;
        }

        self::assertEquals(
            [
                [
                    'level'   => \Feedback::INFO,
                    'message' => 'User preferences successfully updated',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/edition', $redirect_url);
    }

    public function testItRejectsIfItDoesNotKnowTheDefaultFormat(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getPreference')->willReturnMap([
            ['user_edition_default_format', 'text'],
            ['user_csv_separator', 'comma'],
            ['user_csv_dateformat', 'day_month_year'],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $user
            ->expects(self::never())
            ->method('setPreference')
            ->with('user_edition_default_format', 'html');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('user_text_default_format', 'unknown')
            ->withParam('user_csv_separator', 'comma')
            ->withParam('user_csv_dateformat', 'day_month_year')
            ->build();

        $layout_inspector = new LayoutInspector();
        $redirect_url     = null;

        try {
            $this->controller->process(
                $request,
                LayoutBuilder::buildWithInspector($layout_inspector),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $redirect_url = $ex->redirect_url;
        }

        self::assertEquals(
            [
                [
                    'level'   => \Feedback::ERROR,
                    'message' => 'Submitted text format is not valid',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/edition', $redirect_url);
    }

    public function testItRejectsIfItDoesNotKnowTheCSVSeparator(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getPreference')->willReturnMap([
            ['user_edition_default_format', 'text'],
            ['user_csv_separator', 'comma'],
            ['user_csv_dateformat', 'day_month_year'],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $user
            ->expects(self::never())
            ->method('setPreference')
            ->with('user_edition_default_format', 'html');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('user_text_default_format', 'text')
            ->withParam('user_csv_separator', 'unknown')
            ->withParam('user_csv_dateformat', 'day_month_year')
            ->build();

        $layout_inspector = new LayoutInspector();
        $redirect_url     = null;

        try {
            $this->controller->process(
                $request,
                LayoutBuilder::buildWithInspector($layout_inspector),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $redirect_url = $ex->redirect_url;
        }

        self::assertEquals(
            [
                [
                    'level'   => \Feedback::ERROR,
                    'message' => 'Submitted CSV separator is not valid',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/edition', $redirect_url);
    }

    public function testItRejectsIfItDoesNotKnowTheCSVDateFormat(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getPreference')->willReturnMap([
            ['user_edition_default_format', 'text'],
            ['user_csv_separator', 'comma'],
            ['user_csv_dateformat', 'day_month_year'],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $user
            ->expects(self::never())
            ->method('setPreference')
            ->with('user_edition_default_format', 'html');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('user_text_default_format', 'text')
            ->withParam('user_csv_separator', 'comma')
            ->withParam('user_csv_dateformat', 'unknown')
            ->build();

        $layout_inspector = new LayoutInspector();
        $redirect_url     = null;

        try {
            $this->controller->process(
                $request,
                LayoutBuilder::buildWithInspector($layout_inspector),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $redirect_url = $ex->redirect_url;
        }

        self::assertEquals(
            [
                [
                    'level'   => \Feedback::ERROR,
                    'message' => 'Submitted CSV date format is not valid',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/edition', $redirect_url);
    }
}
