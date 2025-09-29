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
use ForgeConfig;
use PHPUnit\Framework\Attributes\TestWith;
use ThemeVariant;
use Tuleap\Date\SelectedDateDisplayPreferenceValidator;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Layout\ThemeVariantColor;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\User\Account\Appearance\FaviconVariant;
use Tuleap\User\Account\Appearance\DarkModeValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UpdateAppearancePreferencesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

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

    #[\Override]
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
        $user->expects($this->never())->method('setLanguageID');

        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');

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
        $user->expects($this->never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');

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
        $user->expects($this->never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');

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
        $user->expects($this->never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');

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
        $user->expects($this->never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->theme_variant->expects($this->once())->method('getVariantColorForUser')->willReturn(ThemeVariantColor::Orange);
        $this->theme_variant->expects($this->once())->method('getAllowedVariantColors')->willReturn([ThemeVariantColor::Orange]);

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

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
        $user->expects($this->never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->theme_variant->expects($this->once())->method('getVariantColorForUser')->willReturn(ThemeVariantColor::Orange);

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

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

    #[TestWith([FaviconVariant::PREFERENCE_VALUE_OFF, false, null])]
    #[TestWith([FaviconVariant::PREFERENCE_VALUE_OFF, '1', true])]
    #[TestWith([FaviconVariant::PREFERENCE_VALUE_ON, false, false])]
    #[TestWith([FaviconVariant::PREFERENCE_VALUE_ON, '1', null])]
    public function testFaviconVariant(
        string $current_favicon_variant,
        false|string $want_favicon_variant,
        ?bool $expected,
    ): void {
        ForgeConfig::setFeatureFlag(FaviconVariant::FEATURE_FLAG, '1');

        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->expects($this->never())->method('setLanguageID');
        $user->method('getPreference')->willReturnMap([
            [FaviconVariant::PREFERENCE_NAME, $current_favicon_variant],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        if ($expected === null) {
            $user->expects($this->never())->method('setPreference');
        } else {
            $user->expects($this->once())
                ->method('setPreference')
                ->with(
                    FaviconVariant::PREFERENCE_NAME,
                    $expected ? FaviconVariant::PREFERENCE_VALUE_ON : FaviconVariant::PREFERENCE_VALUE_OFF
                );
        }

        $builder = HTTPRequestBuilder::get()->withUser($user);
        if ($want_favicon_variant !== false) {
            $builder = $builder->withParam('favicon-variant', $want_favicon_variant);
        }

        $request = $builder->build();

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

        self::assertSame(
            [
                [
                    'level'   => \Feedback::INFO,
                    'message' => $expected === null
                        ? 'Nothing changed'
                        : 'User preferences successfully updated',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        self::assertSame('/account/appearance', $redirect_url);
    }

    public function testItDoesNothingIfUserStillDoesNotWantCondensed(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

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
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

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
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->once())->method('delPreference')->with('display_density');

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

    #[TestWith(['dark', 'dark'])]
    #[TestWith(['light', 'light'])]
    #[TestWith(['system', 'system'])]
    #[TestWith(['', 'light'])]
    #[TestWith([false, 'light'])]
    public function testItDoesNothingIfUserKeepTheSameDarkMode(
        false|string $current_dark_mode,
        string $http_value,
    ): void {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['display_dark_mode', $current_dark_mode],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('display_dark_mode', $http_value)
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

    #[TestWith(['dark', 'system'])]
    #[TestWith(['dark', 'light'])]
    #[TestWith(['light', 'dark'])]
    #[TestWith(['light', 'system'])]
    #[TestWith(['system', 'dark'])]
    #[TestWith(['system', 'light'])]
    #[TestWith([false, 'dark'])]
    #[TestWith([false, 'system'])]
    public function testItChangesIfUserWantAnotherDarkMode(
        false|string $current_dark_mode,
        string $want_dark_mode,
    ): void {
        $user = $this->createMock(\PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getLanguageID')->willReturn('fr_FR');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['display_dark_mode', $current_dark_mode],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $user->expects($this->once())
            ->method('setPreference')
            ->with('display_dark_mode', $want_dark_mode);

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('display_dark_mode', $want_dark_mode)
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
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', '2'],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

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
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', '2'],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

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
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

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
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', 'absolute_first-relative_shown'],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

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
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

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
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', '1'],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->never())->method('setPreference');

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
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', '1'],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->never())->method('updateDB');
        $user->expects($this->once())->method('setPreference')->with('accessibility_mode', '0');

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
        $user->expects($this->once())->method('setLanguageID')->with('en_US');
        $user->method('getPreference')->willReturnMap([
            ['display_density', false],
            ['display_dark_mode', DarkModeValue::default()->value],
            ['accessibility_mode', false],
            ['username_display', false],
            ['relative_dates_display', false],
        ]);

        $this->csrf_token->expects($this->once())->method('check');

        $this->user_manager->expects($this->once())->method('updateDB')->willReturn(true);

        $this->theme_variant->expects($this->once())->method('getVariantColorForUser')->willReturn(ThemeVariantColor::Orange);
        $this->theme_variant->expects($this->once())->method('getAllowedVariantColors')->willReturn([ThemeVariantColor::Orange, ThemeVariantColor::Green]);
        $user
            ->method('setPreference')
            ->willReturnMap([
                ['relative_dates_display', 'absolute_first-relative_shown', true],
                ['username_display', '2', true],
                ['display_dark_mode', 'dark', true],
                ['accessibility_mode', '1', true],
                ['display_density', 'condensed', true],
                ['theme_variant', 'green', true],
            ]);

        $request = HTTPRequestBuilder::get()
            ->withUser($user)
            ->withParam('language_id', 'en_US')
            ->withParam('display_density', 'condensed')
            ->withParam('display_dark_mode', 'dark')
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
