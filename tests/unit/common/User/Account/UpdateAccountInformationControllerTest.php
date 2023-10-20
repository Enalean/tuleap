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

declare(strict_types=1);

namespace Tuleap;

use CSRFSynchronizerToken;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\Account\AccountInformationCollection;
use Tuleap\User\Account\DisplayAccountInformationController;
use Tuleap\User\Account\EmailNotSentException;
use Tuleap\User\Account\EmailUpdater;
use Tuleap\User\Account\UpdateAccountInformationController;
use Tuleap\User\Profile\AvatarGenerator;
use UserManager;

final class UpdateAccountInformationControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var UpdateAccountInformationController
     */
    private $controller;
    /**
     * @var EventDispatcherInterface
     */
    private $event_manager;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var LayoutInspector
     */
    private $layout_inspector;
    /**
     * @var Layout\BaseLayout
     */
    private $layout;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&EmailUpdater
     */
    private $email_updater;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AvatarGenerator
     */
    private $avatar_generator;

    protected function setUp(): void
    {
        $this->event_manager = new class implements EventDispatcherInterface {
            public $disable_real_name_change = false;
            public $disable_email_change     = false;

            public function dispatch(object $event)
            {
                if ($event instanceof AccountInformationCollection) {
                    if ($this->disable_real_name_change) {
                        $event->disableChangeRealName();
                    }
                    if ($this->disable_email_change) {
                        $event->disableChangeEmail();
                    }
                }
                return $event;
            }
        };

        $this->csrf_token = $this->createMock(CSRFSynchronizerToken::class);

        $this->user_manager     = $this->createMock(UserManager::class);
        $this->email_updater    = $this->createMock(EmailUpdater::class);
        $this->avatar_generator = $this->createMock(AvatarGenerator::class);
        $this->controller       = new UpdateAccountInformationController(
            $this->event_manager,
            $this->csrf_token,
            $this->user_manager,
            $this->email_updater,
            $this->avatar_generator,
        );

        $this->layout_inspector = new LayoutInspector();

        $this->layout = LayoutBuilder::buildWithInspector($this->layout_inspector);

        $language = $this->createStub(\BaseLanguage::class);
        $language->method('getText')->willReturn('');

        $this->user = UserTestBuilder::aUser()
            ->withId(110)
            ->withRealName('Alice FooBar')
            ->withEmail('alice@example.com')
            ->withLanguage($language)
            ->withAddDate(940000000)
            ->withTimezone('UTC')
            ->withAvatarUrl("/path/to/avatar.png")
            ->build();
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

    public function testItCheckCSRFToken(): void
    {
        $this->csrf_token->expects(self::once())->method('check')->with(DisplayAccountInformationController::URL);

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItCannotUpdateRealNameWhenEventSaySo(): void
    {
        $this->csrf_token->method('check');

        $this->event_manager->disable_real_name_change = true;

        $this->user_manager->expects(self::never())->method('updateDb');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesRealNameAndAvatarWhenRealNameChanged(): void
    {
        $this->csrf_token->method('check');

        $this->user_manager->expects(self::once())->method('updateDb')->with(
            self::callback(
                static function (\PFUser $user) {
                    return $user->getRealName() === 'Franck Zappa';
                }
            )
        )->willReturn(true);

        $this->avatar_generator->expects(self::once())->method('generate');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('realname', 'Franck Zappa')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(\Feedback::INFO, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('real name successfully updated', $feedback[0]['message']);
    }

    public function testItUpdatesRealNameButNotAvatarWhenRealNameChangedAndUserHasCustomAvatar(): void
    {
        $this->csrf_token->method('check');

        $this->user->setHasCustomAvatar(true);

        $this->user_manager->expects(self::once())->method('updateDb')->with(self::callback(static function (\PFUser $user) {
            return $user->getRealName() === 'Franck Zappa';
        }))->willReturn(true);

        $this->avatar_generator->expects(self::never())->method('generate');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('realname', 'Franck Zappa')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(\Feedback::INFO, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('real name successfully updated', $feedback[0]['message']);
    }

    public function testItDoesntUpdateRealNameWhenNothingChanged(): void
    {
        $this->csrf_token->method('check');
        $this->user_manager->expects(self::never())->method('updateDb');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('realname', 'Alice FooBar')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);

        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(\Feedback::INFO, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('nothing changed', $feedback[0]['message']);
    }

    public function testItThrowsAnErrorWhenRealNameIsNotValid(): void
    {
        $this->csrf_token->method('check');
        $this->user_manager->expects(self::never())->method('updateDb');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('realname', "FooBar \n FooBard")->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(2, $feedback);
        self::assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        self::assertEquals('Real name is not valid', $feedback[0]['message']);
    }

    public function testItDoesntUpdateRealNameWhenDBUpdateFails(): void
    {
        $this->csrf_token->method('check');
        $this->user_manager->method('updateDb')->willReturn(false);

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('realname', 'Foo Bar')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(2, $feedback);
        self::assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('real name was not updated', $feedback[0]['message']);
    }

    public function testItCannotUpdateEmailWhenEventSaySo(): void
    {
        $this->csrf_token->method('check');
        $this->event_manager->disable_email_change = true;

        $this->email_updater->expects(self::never())->method('sendEmailChangeConfirm');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'bob@example.com')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesEmail(): void
    {
        $this->csrf_token->method('check');
        $this->email_updater->expects(self::once())->method('sendEmailChangeConfirm')->with(self::anything(), $this->user);
        $this->user_manager->expects(self::once())->method('updateDb')->with(self::callback(static function (\PFUser $user) {
            return $user->getEmailNew() === 'bob@example.com' && $user->getConfirmHash() != '';
        }))->willReturn(true);

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'bob@example.com')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(\Feedback::INFO, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('email was successfully saved', $feedback[0]['message']);
    }

    public function testItUpdatesEmailButDbUpdateFails(): void
    {
        $this->csrf_token->method('check');
        $this->email_updater->expects(self::never())->method('sendEmailChangeConfirm');
        $this->user_manager->method('updateDb')->willReturn(false);

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'bob@example.com')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(2, $feedback);
        self::assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('email was not updated', $feedback[0]['message']);
    }

    public function testItUpdatesEmailWithoutUpdatingRealname(): void
    {
        $this->csrf_token->method('check');
        $this->email_updater->expects(self::once())->method('sendEmailChangeConfirm')->with(self::anything(), $this->user);
        $this->user_manager->expects(self::once())->method('updateDb')->with(self::callback(static function (\PFUser $user) {
            return $user->getEmailNew() === 'bob@example.com';
        }))->willReturn(true);

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'bob@example.com')->withParam('realname', 'Alice FooBar')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(\Feedback::INFO, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('email was successfully saved', $feedback[0]['message']);
        self::assertStringNotContainsStringIgnoringCase('nothing changed', $feedback[0]['message']);
    }

    public function testItDoesntUpdatesEmailWhenNoChanges(): void
    {
        $this->csrf_token->method('check');
        $this->email_updater->expects(self::never())->method('sendEmailChangeConfirm');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'alice@example.com')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(\Feedback::INFO, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('nothing changed', $feedback[0]['message']);
    }

    public function testItReportsAnErrorWhenMailCannotBeSent(): void
    {
        $this->csrf_token->method('check');
        $this->email_updater->method('sendEmailChangeConfirm')->willThrowException(new EmailNotSentException());
        $this->user_manager->expects(self::once())->method('updateDb')->with(self::callback(static function (\PFUser $user) {
            return $user->getEmailNew() === 'bob@example.com';
        }))->willReturn(true);

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'bob@example.com')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('mail was not accepted for the delivery', $feedback[0]['message']);
    }

    public function testItDoesntUpdateWithInvalidTimezone(): void
    {
        $this->csrf_token->method('check');
        $this->user_manager->expects(self::never())->method('updateDb');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('timezone', 'Frank Zappa')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(2, $feedback);
        self::assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('invalid timezone', $feedback[0]['message']);
    }

    public function testItDoesntUpdateWithNoTimezoneChange(): void
    {
        $this->csrf_token->method('check');
        $this->user_manager->expects(self::never())->method('updateDb');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('timezone', 'UTC')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(\Feedback::INFO, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('nothing changed', $feedback[0]['message']);
    }

    public function testItUpdatesTimezone(): void
    {
        $this->csrf_token->method('check');
        $this->user_manager->expects(self::once())->method('updateDb')->with(self::callback(static function (\PFUser $user) {
            return $user->getTimezone() === 'Europe/Berlin';
        }))->willReturn(true);

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('timezone', 'Europe/Berlin')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(\Feedback::INFO, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('timezone successfully updated', $feedback[0]['message']);
    }

    public function testItDoesntUpdateTimezoneWhenDBUpdateFails(): void
    {
        $this->csrf_token->method('check');
        $this->user_manager->method('updateDb')->willReturn(false);

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($this->user)->withParam('timezone', 'Europe/Berlin')->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(2, $feedback);
        self::assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('timezone was not updated', $feedback[0]['message']);
    }

    public function testItUpdatesEverythingAtOnce(): void
    {
        $this->csrf_token->method('check');

        $this->email_updater->expects(self::once())->method('sendEmailChangeConfirm')->with(self::anything(), $this->user);
        $this->user_manager->method('updateDb')->willReturnCallback(
            function (PFUser $user): bool {
                if (
                    $user->getRealName() === 'Franck Zappa' ||
                    $user->getTimezone() === 'Europe/Berlin' ||
                    $user->getEmailNew() === 'bob@example.com'
                ) {
                    return true;
                }

                return false;
            }
        );

        $this->avatar_generator->expects(self::once())->method('generate');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()
                    ->withUser($this->user)
                    ->withParam('email', 'bob@example.com')
                    ->withParam('realname', 'Franck Zappa')
                    ->withParam('timezone', 'Europe/Berlin')
                    ->build(),
                $this->layout,
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(3, $feedback);
        self::assertEquals(\Feedback::INFO, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('real name successfully updated', $feedback[0]['message']);
        self::assertEquals(\Feedback::INFO, $feedback[1]['level']);
        self::assertStringContainsStringIgnoringCase('email was successfully saved', $feedback[1]['message']);
        self::assertEquals(\Feedback::INFO, $feedback[2]['level']);
        self::assertStringContainsStringIgnoringCase('timezone successfully updated', $feedback[2]['message']);
    }
}
