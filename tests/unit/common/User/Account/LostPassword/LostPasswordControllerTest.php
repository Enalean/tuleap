<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\User\Account\LostPassword;

use Codendi_Mail;
use PFUser;
use Psr\Log\NullLogger;
use TemplateRendererFactory;
use Tuleap_Template_Mail;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Mail\MailFactory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\Password\Reset\Creator;
use Tuleap\User\Password\Reset\RecentlyCreatedCodeException;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\User\RetrieveUserByUserName;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LostPasswordControllerTest extends TestCase
{
    use \Tuleap\TemporaryTestDirectory;

    public function testPasswordResetSamePageForExistantOrInexistantUsers(): void
    {
        $renderer                     = TemplateRendererFactoryBuilder::get()
            ->withPath($this->getTmpDir())
            ->build();
        $anonymous_user               = UserTestBuilder::anAnonymousUser()->build();
        $retrieve_user_by_username    = ProvideAndRetrieveUserStub::build($anonymous_user)->withUsers([$this->createValidUser()]);
        $password_reset_token_creator = self::createStub(Creator::class);
        $password_reset_token_creator
            ->method('create')
            ->willReturn(new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $mail = self::createMock(Codendi_Mail::class);

        ob_start();
        $this->processLostPasswordController(
            $renderer,
            $retrieve_user_by_username,
            $password_reset_token_creator,
            $mail,
            'nonexistant_user'
        );
        $content_page_inexistant_user = ob_get_clean();

        $mail = self::createCodendiMailMock();

        ob_start();
        $this->processLostPasswordController(
            $renderer,
            $retrieve_user_by_username,
            $password_reset_token_creator,
            $mail,
            'valid_user'
        );
        $content_page_existant_user = ob_get_clean();

        self::assertSame($content_page_inexistant_user, $content_page_existant_user);
    }

    public function testPasswordResetSamePageTwoReset(): void
    {
        $renderer                     = TemplateRendererFactoryBuilder::get()
            ->withPath($this->getTmpDir())
            ->build();
        $anonymous_user               = UserTestBuilder::anAnonymousUser()->build();
        $retrieve_user_by_username    = ProvideAndRetrieveUserStub::build($anonymous_user)->withUsers([$this->createValidUser()]);
        $password_reset_token_creator = self::createStub(Creator::class);
        $password_reset_token_creator
            ->method('create')
            ->willReturn(new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()));
        $mail = self::createCodendiMailMock();

        ob_start();
        $this->processLostPasswordController(
            $renderer,
            $retrieve_user_by_username,
            $password_reset_token_creator,
            $mail,
            'valid_user'
        );
        $page_content_first_reset = ob_get_clean();

        $password_reset_token_creator
            ->method('create')
            ->willThrowException(new RecentlyCreatedCodeException());
        $mail = self::createMock(Codendi_Mail::class);

        ob_start();
        $this->processLostPasswordController(
            $renderer,
            $retrieve_user_by_username,
            $password_reset_token_creator,
            $mail,
            'valid_user'
        );
        $page_content_second_reset = ob_get_clean();

        self::assertSame($page_content_first_reset, $page_content_second_reset);
    }

    private function createValidUser(): PFUser
    {
        $user = UserTestBuilder::anActiveUser()
            ->withUserName('valid_user')
            ->withRealName('Valid User')
            ->withEmail('valid_user@enalean.com')
            ->withPassword('password')
            ->build();
        return $user;
    }

    private function createCodendiMailMock(): Codendi_Mail
    {
        $mail = self::createMock(Codendi_Mail::class);
        $mail->expects($this->once())->method('setLookAndFeelTemplate');
        $mail->expects($this->once())->method('setFrom');
        $mail->expects($this->once())->method('setTo');
        $mail->expects($this->once())->method('setSubject');
        $mail->expects($this->once())->method('setBodyHtml');
        $mail->expects($this->once())->method('setBodyText');
        $mail->expects($this->once())->method('send')->willReturn(true);
        return $mail;
    }

    private function processLostPasswordController(
        TemplateRendererFactory $renderer,
        RetrieveUserByUserName $retrieve_user_by_username,
        Creator $password_reset_token_creator,
        Codendi_Mail $mail,
        string $loginname,
    ): void {
        $this->getLostPasswordController(
            $renderer,
            $retrieve_user_by_username,
            $password_reset_token_creator,
            $mail
        )->process(
            HTTPRequestBuilder::get()
                ->withParams([
                    'form_loginname' => $loginname,
                ])
                ->build(),
            new TestLayout(new LayoutInspector()),
            [],
        );
    }

    private function getLostPasswordController(
        TemplateRendererFactory $renderer_factory,
        RetrieveUserByUserName $retrieve_user_by_username,
        Creator $password_reset_token_creator,
        Codendi_Mail $mail,
    ): LostPasswordController {
        $event_manager = EventDispatcherStub::withIdentityCallback();
        $core_assets   = new \Tuleap\Layout\IncludeCoreAssets();

        $mail_factory = self::createStub(MailFactory::class);
        $mail_factory->method('getMail')->willReturn($mail);

        return new LostPasswordController(
            $retrieve_user_by_username,
            $password_reset_token_creator,
            new \Tuleap\User\Password\Reset\ResetTokenSerializer(),
            new \Tuleap\Language\LocaleSwitcher(),
            $renderer_factory,
            $event_manager,
            $core_assets,
            new DisplayLostPasswordController(
                $renderer_factory,
                $core_assets,
                $event_manager,
            ),
            new NullLogger(),
            self::createStub(Tuleap_Template_Mail::class),
            $mail_factory
        );
    }
}
