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
use ThemeVariant;
use Tuleap\date\SelectedDateDisplayPreferenceValidator;
use Tuleap\Layout\ThemeVariantColor;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;

final class UpdateAppearancePreferencesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \BaseLanguage&\PHPUnit\Framework\MockObject\MockObject
     */
    private $language;
    /**
     * @var UpdateAppearancePreferences
     */
    private $controller;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ThemeVariant
     */
    private $theme_variant;

    public function setUp(): void
    {
        $this->csrf_token    = $this->createMock(CSRFSynchronizerToken::class);
        $this->user_manager  = $this->createMock(\UserManager::class);
        $this->language      = $this->createMock(\BaseLanguage::class);
        $this->theme_variant = $this->createMock(ThemeVariant::class);

        $this->language->method('isLanguageSupported')->willReturnMap([
            ['fr_FR', true],
            ['en_US', true],
            ['pt_BR', false],
        ]);

        $this->controller = new UpdateAppearancePreferences(
            $this->csrf_token,
            $this->user_manager,
            $this->language,
            $this->theme_variant,
            new SelectedDateDisplayPreferenceValidator()
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
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->expects(self::never())->method('setLanguageID');

        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');

        $request = HTTPRequestBuilder::get()->withUser($user)->build();

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
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfLanguageIsNotSupported(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->expects(self::never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('language_id', 'pt_BR')
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
                    'message' => 'The submitted language is not supported.',
                ],
                [
                    'level'   => \Feedback::INFO,
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfUserKeepsItsLanguage(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->expects(self::never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('language_id', 'fr_FR')
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
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfColorIsNotSubmitted(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->expects(self::never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');

        $request = HTTPRequestBuilder::get()->withUser($user)->build();

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
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfColorIsNotSupported(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->expects(self::never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->theme_variant->expects(self::once())->method('getVariantColorForUser')->willReturn(ThemeVariantColor::Orange);
        $this->theme_variant->expects(self::once())->method('getAllowedVariantColors')->willReturn([ThemeVariantColor::Orange]);

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('color', 'red')
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
                    'message' => 'The chosen color is not allowed.',
                ],
                [
                    'level'   => \Feedback::INFO,
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfUserKeepsItsColor(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->expects(self::never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->theme_variant->expects(self::once())->method('getVariantColorForUser')->willReturn(ThemeVariantColor::Orange);

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('color', 'orange')
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
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfUserStillDoesNotWantCondensed(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('display_density', '')
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
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfUserStillWantsCondensed(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', 'condensed'],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('display_density', 'condensed')
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
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItRemovesTheCondensedMode(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', 'condensed'],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::once())->method('delPreference')->with('display_density');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('display_density', '')
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
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfUserKeepsTheSameUsernameDisplay(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', '2'],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('username_display', '2')
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
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testRejectsInvalidUsernameDisplay(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', '2'],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('username_display', '666')
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
                    'message' => 'Submitted username display is not valid.',
                ],
                [
                    'level'   => \Feedback::INFO,
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testRejectsInvalidRelativeDatesDisplay(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('relative-dates-display', '666')
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
                    'message' => 'Submitted relative dates display is not valid.',
                ],
                [
                    'level'   => \Feedback::INFO,
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfUserKeepsTheSameRelativeDatesDisplay(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', 'absolute_first-relative_shown'],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('relative-dates-display', 'absolute_first-relative_shown')
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
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfUserStillDoesNotWantAccessibility(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('accessibility_mode', '')
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
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfUserStillWantsAccessibility(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', '1'],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('accessibility_mode', '1')
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
                    'message' => 'Nothing changed',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItRemovesTheAccessibilityMode(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', '1'],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::never())->method('updateDB');
        $user->expects(self::once())->method('setPreference')->with('accessibility_mode', '0');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('accessibility_mode', '')
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
        self::assertEquals('/account/appearance', $redirect_url);
    }

    public function testItUpdatesTheUser(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->expects(self::once())->method('setLanguageID')->with('en_US');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects(self::once())->method('check');

        $this->user_manager->expects(self::once())->method('updateDB')->willReturn(true);

        $this->theme_variant->expects(self::once())->method('getVariantColorForUser')->willReturn(ThemeVariantColor::Orange);
        $this->theme_variant->expects(self::once())->method('getAllowedVariantColors')->willReturn([ThemeVariantColor::Orange, ThemeVariantColor::Green]);
        $user
            ->method('setPreference')
            ->willReturnMap([
                ['relative_dates_display', 'absolute_first-relative_shown', true],
                ['username_display', '2', true],
                ['accessibility_mode', '1', true],
                ['display_density', 'condensed', true],
                ['theme_variant', 'green', true],
            ]);

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('language_id', 'en_US')
            ->withParam('display_density', 'condensed')
            ->withParam('accessibility_mode', '1')
            ->withParam('color', 'green')
            ->withParam('username_display', '2')
            ->withParam('relative-dates-display', 'absolute_first-relative_shown')
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
        self::assertEquals('/account/appearance', $redirect_url);
    }
}
