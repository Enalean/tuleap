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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;

class UpdateEditionControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CSRFSynchronizerToken|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $csrf_token;
    /**
     * @var UpdateEditionController
     */
    private $controller;

    public function setUp(): void
    {
        $this->csrf_token = Mockery::mock(CSRFSynchronizerToken::class);

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
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false]);
        $user->shouldReceive('getPreference')->with('user_edition_default_format')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('user_csv_separator')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('user_csv_dateformat')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $user
            ->shouldReceive('setPreference')
            ->with('user_edition_default_format', 'html')
            ->once()
            ->andReturnTrue();
        $user
            ->shouldReceive('setPreference')
            ->with('user_csv_separator', 'comma')
            ->once()
            ->andReturnTrue();
        $user
            ->shouldReceive('setPreference')
            ->with('user_csv_dateformat', 'day_month_year')
            ->once()
            ->andReturnTrue();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('user_text_default_format', 'html')
            ->withParam('user_csv_separator', 'comma')
            ->withParam('user_csv_dateformat', 'day_month_year')
            ->build();

        $layout_inspector = new LayoutInspector();
        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $this->assertEquals(
            [
                [
                    'level'   => \Feedback::INFO,
                    'message' => 'User preferences successfully updated'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/edition', $layout_inspector->getRedirectUrl());
    }

    public function testItRejectsIfItDoesNotKnowTheDefaultFormat(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false]);
        $user->shouldReceive('getPreference')->with('user_edition_default_format')->andReturn('text');
        $user->shouldReceive('getPreference')->with('user_csv_separator')->andReturn('comma');
        $user->shouldReceive('getPreference')->with('user_csv_dateformat')->andReturn('day_month_year');

        $this->csrf_token->shouldReceive('check')->once();

        $user
            ->shouldReceive('setPreference')
            ->with('user_edition_default_format', 'html')
            ->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('user_text_default_format', 'unknown')
            ->withParam('user_csv_separator', 'comma')
            ->withParam('user_csv_dateformat', 'day_month_year')
            ->build();

        $layout_inspector = new LayoutInspector();
        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $this->assertEquals(
            [
                [
                    'level'   => \Feedback::ERROR,
                    'message' => 'Submitted text format is not valid'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/edition', $layout_inspector->getRedirectUrl());
    }

    public function testItRejectsIfItDoesNotKnowTheCSVSeparator(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false]);
        $user->shouldReceive('getPreference')->with('user_edition_default_format')->andReturn('text');
        $user->shouldReceive('getPreference')->with('user_csv_separator')->andReturn('comma');
        $user->shouldReceive('getPreference')->with('user_csv_dateformat')->andReturn('day_month_year');

        $this->csrf_token->shouldReceive('check')->once();

        $user
            ->shouldReceive('setPreference')
            ->with('user_edition_default_format', 'html')
            ->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('user_text_default_format', 'text')
            ->withParam('user_csv_separator', 'unknown')
            ->withParam('user_csv_dateformat', 'day_month_year')
            ->build();

        $layout_inspector = new LayoutInspector();
        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $this->assertEquals(
            [
                [
                    'level'   => \Feedback::ERROR,
                    'message' => 'Submitted CSV separator is not valid'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/edition', $layout_inspector->getRedirectUrl());
    }

    public function testItRejectsIfItDoesNotKnowTheCSVDateFormat(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false]);
        $user->shouldReceive('getPreference')->with('user_edition_default_format')->andReturn('text');
        $user->shouldReceive('getPreference')->with('user_csv_separator')->andReturn('comma');
        $user->shouldReceive('getPreference')->with('user_csv_dateformat')->andReturn('day_month_year');

        $this->csrf_token->shouldReceive('check')->once();

        $user
            ->shouldReceive('setPreference')
            ->with('user_edition_default_format', 'html')
            ->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('user_text_default_format', 'text')
            ->withParam('user_csv_separator', 'comma')
            ->withParam('user_csv_dateformat', 'unknown')
            ->build();

        $layout_inspector = new LayoutInspector();
        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $this->assertEquals(
            [
                [
                    'level'   => \Feedback::ERROR,
                    'message' => 'Submitted CSV date format is not valid'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/edition', $layout_inspector->getRedirectUrl());
    }
}
