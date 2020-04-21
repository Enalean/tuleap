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

use Codendi_Mail_Interface;
use CSRFSynchronizerToken;
use Mockery as M;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\Account\UpdateNotificationsPreferences;
use PHPUnit\Framework\TestCase;
use UserManager;

class UpdateNotificationsControllerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var UpdateNotificationsPreferences
     */
    private $controller;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;

    public function setUp(): void
    {
        $this->user         = UserTestBuilder::aUser()->withId(120)->build();
        $this->user->_preferencesdao = M::spy(\UserPreferencesDao::class);

        $this->user_manager = M::mock(UserManager::class);
        $this->csrf_token   = M::mock(CSRFSynchronizerToken::class);
        $this->controller   = new UpdateNotificationsPreferences($this->csrf_token, $this->user_manager);
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

    public function testItChecksCSRFToken(): void
    {
        $this->csrf_token->shouldReceive('check')->with('/account/notifications')->once();
        $this->user_manager->shouldReceive('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItRedirects(): void
    {
        $this->csrf_token->shouldReceive('check');
        $this->user_manager->shouldReceive('updateDb');

        $layout_inspector = new LayoutInspector();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $this->assertEquals('/account/notifications', $layout_inspector->getRedirectUrl());
    }

    public function testItActivatesMailSiteUpdate(): void
    {
        $this->csrf_token->shouldReceive('check');

        $this->user_manager->shouldReceive('updateDb')->withArgs(function (\PFUser $user) {
            return $user->getMailSiteUpdates() === 1;
        })->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('site_email_updates', '1')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDeactivatesMailSiteUpdate(): void
    {
        $this->csrf_token->shouldReceive('check');

        $this->user->setMailSiteUpdates('1');

        $this->user_manager->shouldReceive('updateDb')->withArgs(function (\PFUser $user) {
            return $user->getMailSiteUpdates() === 0;
        })->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDoesntUpdateUserWhenMailSiteUpdateAlreadyActive(): void
    {
        $this->csrf_token->shouldReceive('check');

        $this->user->setMailSiteUpdates('1');

        $this->user_manager->shouldNotReceive('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('site_email_updates', '1')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDoesntUpdateUserWhenMailSiteUpdateAlreadyInActive(): void
    {
        $this->csrf_token->shouldReceive('check');

        $this->user->setMailSiteUpdates('0');

        $this->user_manager->shouldNotReceive('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('site_email_updates', '0')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItActivatesMailAdditionalCommunityMailing(): void
    {
        $this->csrf_token->shouldReceive('check');

        $this->user_manager->shouldReceive('updateDb')->withArgs(function (\PFUser $user) {
            return $user->getMailVA() === 1;
        })->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('site_email_community', '1')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDeactivatesMailAdditionalCommunityMailing(): void
    {
        $this->csrf_token->shouldReceive('check');

        $this->user->setMailVA('1');

        $this->user_manager->shouldReceive('updateDb')->withArgs(function (\PFUser $user) {
            return $user->getMailVA() === 0;
        })->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDoesntUpdateUserWhenMailAdditionalCommunityAlreadyInActive(): void
    {
        $this->csrf_token->shouldReceive('check');

        $this->user->setMailVA('1');

        $this->user_manager->shouldNotReceive('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('site_email_community', '1')->build(),
            LayoutBuilder::build(),
            []
        );
    }


    public function testItUpdatesEmailFormatPreferenceToHtml(): void
    {
        $this->csrf_token->shouldReceive('check');

        $this->user->_preferences[Codendi_Mail_Interface::PREF_FORMAT] = Codendi_Mail_Interface::FORMAT_HTML;

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email_format', 'html')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesEmailFormatPreferenceToText(): void
    {
        $this->csrf_token->shouldReceive('check');

        $this->user->_preferences[Codendi_Mail_Interface::PREF_FORMAT] = Codendi_Mail_Interface::FORMAT_TEXT;

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email_format', 'text')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDoesntUpdateMailFormatPreferenceWhenPreferenceDoesntChange(): void
    {
        $this->csrf_token->shouldReceive('check');

        $this->user->_preferences[Codendi_Mail_Interface::PREF_FORMAT] = Codendi_Mail_Interface::FORMAT_HTML;

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email_format', 'html')->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
