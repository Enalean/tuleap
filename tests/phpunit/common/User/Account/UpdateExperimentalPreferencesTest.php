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

namespace TuleapCodingStandard\User\Account;

use CSRFSynchronizerToken;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\User\Account\UpdateExperimentalPreferences;
use Tuleap\User\Account\UpdateNotificationsPreferences;

class UpdateExperimentalPreferencesTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var UpdateNotificationsPreferences
     */
    private $controller;
    /**
     * @var CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;

    public function setUp(): void
    {
        $this->csrf_token = M::mock(CSRFSynchronizerToken::class);
        $this->controller = new UpdateExperimentalPreferences($this->csrf_token);
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

    public function testItDoesNothingIfUserStillDoesNotWantLabMode(): void
    {
        $user = M::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'useLabFeatures' => false]);
        $user->shouldReceive('setLabFeatures')->never();

        $this->csrf_token->shouldReceive('check')->once();

        $request = HTTPRequestBuilder::get()
                                     ->withUser($user)
                                     ->withParam('lab_features', '0')
                                     ->build();

        $layout_inspector = new LayoutInspector();
        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $this->assertEquals(
            [['level'   => \Feedback::INFO,
              'message' => 'Nothing changed']],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/experimental', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfUserStillWantsLabMode(): void
    {
        $user = M::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'useLabFeatures' => true]);
        $user->shouldReceive('setLabFeatures')->never();

        $this->csrf_token->shouldReceive('check')->once();

        $request = HTTPRequestBuilder::get()
                                     ->withUser($user)
                                     ->withParam('lab_features', '1')
                                     ->build();

        $layout_inspector = new LayoutInspector();
        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $this->assertEquals(
            [['level'   => \Feedback::INFO,
              'message' => 'Nothing changed']],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/experimental', $layout_inspector->getRedirectUrl());
    }

    public function testItSavesTheNewLabMode(): void
    {
        $user = M::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'useLabFeatures' => false]);
        $user->shouldReceive('setLabFeatures')->with(true)->once()->andReturnTrue();

        $this->csrf_token->shouldReceive('check')->once();

        $request = HTTPRequestBuilder::get()
                                     ->withUser($user)
                                     ->withParam('lab_features', '1')
                                     ->build();

        $layout_inspector = new LayoutInspector();
        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $this->assertEquals(
            [['level'   => \Feedback::INFO,
              'message' => 'User preferences successfully updated']],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/experimental', $layout_inspector->getRedirectUrl());
    }

    public function testItWarnsTheUserThatSomethingIsWrong(): void
    {
        $user = M::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'useLabFeatures' => false]);
        $user->shouldReceive('setLabFeatures')->with(true)->once()->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $request = HTTPRequestBuilder::get()
                                     ->withUser($user)
                                     ->withParam('lab_features', '1')
                                     ->build();

        $layout_inspector = new LayoutInspector();
        $this->controller->process(
            $request,
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $this->assertEquals(
            [['level'   => \Feedback::ERROR,
              'message' => 'Unable to update user preferences']],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/experimental', $layout_inspector->getRedirectUrl());
    }
}
