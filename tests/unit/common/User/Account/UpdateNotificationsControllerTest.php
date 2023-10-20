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

namespace Tuleap\User\Account;

use Codendi_Mail_Interface;
use CSRFSynchronizerToken;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use UserManager;

final class UpdateNotificationsControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UpdateNotificationsPreferences $controller;
    private \PFUser&\PHPUnit\Framework\MockObject\MockObject $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;

    public function setUp(): void
    {
        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(120);
        $this->user->method('isAnonymous')->willReturn(false);
        $this->user->preferencesdao = $this->createMock(\UserPreferencesDao::class);

        $this->user_manager = $this->createMock(UserManager::class);
        $this->csrf_token   = $this->createMock(CSRFSynchronizerToken::class);
        $this->controller   = new UpdateNotificationsPreferences($this->csrf_token, $this->user_manager, new \EventManager());
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
        $this->csrf_token->expects(self::once())->method('check')->with('/account/notifications');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(0);
        $this->user
            ->method('getMailVA')
            ->willReturn(0);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->user_manager->method('updateDb');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItRedirects(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(0);
        $this->user
            ->method('getMailVA')
            ->willReturn(0);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->user_manager->method('updateDb');

        $this->expectExceptionObject(new LayoutInspectorRedirection('/account/notifications'));
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItActivatesMailSiteUpdate(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(0);
        $this->user
            ->method('getMailVA')
            ->willReturn(0);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->user
            ->expects(self::once())
            ->method('setMailSiteUpdates')
            ->with(1);
        $this->user_manager->expects(self::once())->method('updateDb');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('site_email_updates', '1')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDeactivatesMailSiteUpdate(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(1);
        $this->user
            ->method('getMailVA')
            ->willReturn(0);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->user
            ->expects(self::once())
            ->method('setMailSiteUpdates')
            ->with(0);
        $this->user_manager->expects(self::once())->method('updateDb');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDoesntUpdateUserWhenMailSiteUpdateAlreadyActive(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(1);
        $this->user
            ->method('getMailVA')
            ->willReturn(0);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->user_manager->expects(self::never())->method('updateDb');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('site_email_updates', '1')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDoesntUpdateUserWhenMailSiteUpdateAlreadyInActive(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(0);
        $this->user
            ->method('getMailVA')
            ->willReturn(0);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->user_manager->expects(self::never())->method('updateDb');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('site_email_updates', '0')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItActivatesMailAdditionalCommunityMailing(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(0);
        $this->user
            ->method('getMailVA')
            ->willReturn(0);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->user
            ->expects(self::once())
            ->method('setMailVA')
            ->with(1);
        $this->user_manager->expects(self::once())->method('updateDb');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('site_email_community', '1')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDeactivatesMailAdditionalCommunityMailing(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(0);
        $this->user
            ->method('getMailVA')
            ->willReturn(1);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->user
            ->expects(self::once())
            ->method('setMailVA')
            ->with(0);
        $this->user_manager->expects(self::once())->method('updateDb');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDoesntUpdateUserWhenMailAdditionalCommunityAlreadyInActive(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(0);
        $this->user
            ->method('getMailVA')
            ->willReturn(1);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->user_manager->expects(self::never())->method('updateDb');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('site_email_community', '1')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesEmailFormatPreferenceToHtml(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(0);
        $this->user
            ->method('getMailVA')
            ->willReturn(0);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email_format', 'html')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesEmailFormatPreferenceToText(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(0);
        $this->user
            ->method('getMailVA')
            ->willReturn(0);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_TEXT);

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email_format', 'text')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDoesntUpdateMailFormatPreferenceWhenPreferenceDoesntChange(): void
    {
        $this->csrf_token->method('check');

        $this->user
            ->method('getMailSiteUpdates')
            ->willReturn(0);
        $this->user
            ->method('getMailVA')
            ->willReturn(0);
        $this->user
            ->method('getPreference')
            ->with(Codendi_Mail_Interface::PREF_FORMAT)
            ->willReturn(Codendi_Mail_Interface::FORMAT_HTML);

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email_format', 'html')->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
