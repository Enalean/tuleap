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
use ThemeVariant;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;

class UpdateAppearancePreferencesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CSRFSynchronizerToken|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $csrf_token;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var \BaseLanguage|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $language;
    /**
     * @var UpdateAppearancePreferences
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ThemeVariant
     */
    private $theme_variant;

    public function setUp(): void
    {
        $this->csrf_token    = Mockery::mock(CSRFSynchronizerToken::class);
        $this->user_manager  = Mockery::mock(\UserManager::class);
        $this->language      = Mockery::mock(\BaseLanguage::class);
        $this->theme_variant = Mockery::mock(ThemeVariant::class);

        $this->theme_variant->shouldReceive('getAllowedVariants')->andReturn(
            ['FlamingParrot_Orange', 'FlamingParrot_Green']
        );

        $this->language->shouldReceive('isLanguageSupported')->with('fr_FR')->andReturnTrue();
        $this->language->shouldReceive('isLanguageSupported')->with('en_US')->andReturnTrue();
        $this->language->shouldReceive('isLanguageSupported')->with('pt_BR')->andReturnFalse();

        $this->controller = new UpdateAppearancePreferences(
            $this->csrf_token,
            $this->user_manager,
            $this->language,
            $this->theme_variant
        );
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

    public function testItDoesNothingIfLanguageIsNotSubmitted(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('setLanguageID')->never();
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();

        $request = HTTPRequestBuilder::get()->withUser($user)->build();

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
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfLanguageIsNotSupported(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('setLanguageID')->never();
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('language_id', 'pt_BR')
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
                    'message' => 'The submitted language is not supported.'
                ],
                [
                    'level'   => \Feedback::INFO,
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfUserKeepsItsLanguage(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('setLanguageID')->never();
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('language_id', 'fr_FR')
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
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfColorIsNotSubmitted(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('setLanguageID')->never();
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();

        $request = HTTPRequestBuilder::get()->withUser($user)->build();

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
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfColorIsNotSupported(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('setLanguageID')->never();
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->theme_variant->shouldReceive('getVariantForUser')->once()->andReturn('FlamingParrot_Orange');

        $this->user_manager->shouldReceive('updateDB')->never();
        $user->shouldReceive('setPreference')->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('color', 'red')
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
                    'message' => 'The chosen color is not allowed.'
                ],
                [
                    'level'   => \Feedback::INFO,
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfUserKeepsItsColor(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('setLanguageID')->never();
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->theme_variant->shouldReceive('getVariantForUser')->once()->andReturn('FlamingParrot_Orange');

        $this->user_manager->shouldReceive('updateDB')->never();
        $user->shouldReceive('setPreference')->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('color', 'orange')
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
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfUserStillDoesNotWantCondensed(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();
        $user->shouldReceive('setPreference')->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('display_density', '')
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
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfUserStillWantsCondensed(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('getPreference')->with('display_density')->andReturn('condensed');
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();
        $user->shouldReceive('setPreference')->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('display_density', 'condensed')
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
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItRemovesTheCondensedMode(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('getPreference')->with('display_density')->andReturn('condensed');
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();
        $user->shouldReceive('delPreference')->with('display_density')->once()->andReturnTrue();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('display_density', '')
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
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfUserKeepsTheSameUsernameDisplay(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturn('2');

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();
        $user->shouldReceive('setPreference')->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('username_display', '2')
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
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testRejectsInvalidUsernameDisplay(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturn('2');

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();
        $user->shouldReceive('setPreference')->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('username_display', '666')
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
                    'message' => 'Submitted username display is not valid.'
                ],
                [
                    'level'   => \Feedback::INFO,
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfUserStillDoesNotWantAccessibility(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();
        $user->shouldReceive('setPreference')->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('accessibility_mode', '')
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
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItDoesNothingIfUserStillWantsAccessibility(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturn('1');
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();
        $user->shouldReceive('setPreference')->never();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('accessibility_mode', '1')
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
                    'message' => 'Nothing changed'
                ]
            ],
            $layout_inspector->getFeedback()
        );
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItRemovesTheAccessibilityMode(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturn('1');
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->never();
        $user->shouldReceive('setPreference')->with('accessibility_mode', '0')->once()->andReturnTrue();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('accessibility_mode', '')
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
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }

    public function testItUpdatesTheUser(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive(['isAnonymous' => false, 'getLanguageID' => 'fr_FR']);
        $user->shouldReceive('setLanguageID')->with('en_US')->once();
        $user->shouldReceive('getPreference')->with('display_density')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('accessibility_mode')->andReturnFalse();
        $user->shouldReceive('getPreference')->with('username_display')->andReturnFalse();

        $this->csrf_token->shouldReceive('check')->once();

        $this->user_manager->shouldReceive('updateDB')->once()->andReturnTrue();

        $this->theme_variant->shouldReceive('getVariantForUser')->once()->andReturn('FlamingParrot_Orange');
        $user
            ->shouldReceive('setPreference')
            ->with('theme_variant', 'FlamingParrot_Green')
            ->once()
            ->andReturnTrue();
        $user
            ->shouldReceive('setPreference')
            ->with('display_density', 'condensed')
            ->once()
            ->andReturnTrue();
        $user
            ->shouldReceive('setPreference')
            ->with('accessibility_mode', '1')
            ->once()
            ->andReturnTrue();
        $user
            ->shouldReceive('setPreference')
            ->with('username_display', '2')
            ->once()
            ->andReturnTrue();

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('language_id', 'en_US')
            ->withParam('display_density', 'condensed')
            ->withParam('accessibility_mode', '1')
            ->withParam('color', 'green')
            ->withParam('username_display', '2')
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
        $this->assertEquals('/account/appearance', $layout_inspector->getRedirectUrl());
    }
}
