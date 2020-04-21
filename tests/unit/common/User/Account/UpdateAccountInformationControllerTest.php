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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\Account\AccountInformationCollection;
use Tuleap\User\Account\DisplayAccountInformationController;
use Tuleap\User\Account\EmailNotSentException;
use Tuleap\User\Account\EmailUpdater;
use Tuleap\User\Account\UpdateAccountInformationController;
use PHPUnit\Framework\TestCase;
use UserManager;

final class UpdateAccountInformationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|UserManager
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
     * @var M\LegacyMockInterface|M\MockInterface|EmailUpdater
     */
    private $email_updater;

    protected function setUp(): void
    {
        $this->event_manager = new class implements EventDispatcherInterface {
            public $disable_real_name_change = false;
            public $disable_email_change = false;

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

        $this->csrf_token = M::mock(CSRFSynchronizerToken::class);
        $this->csrf_token->shouldReceive('check')->byDefault();

        $this->user_manager = M::mock(UserManager::class);
        $this->email_updater = M::mock(EmailUpdater::class);
        $this->controller = new UpdateAccountInformationController(
            $this->event_manager,
            $this->csrf_token,
            $this->user_manager,
            $this->email_updater,
        );

        $this->layout_inspector = new LayoutInspector();

        $this->layout = LayoutBuilder::buildWithInspector($this->layout_inspector);

        $this->user = UserTestBuilder::aUser()
            ->withId(110)
            ->withRealName('Alice FooBar')
            ->withEmail('alice@example.com')
            ->withLanguage(M::spy(\BaseLanguage::class))
            ->withAddDate(940000000)
            ->withTimezone('GMT')
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
        $this->csrf_token->shouldReceive('check')->with(DisplayAccountInformationController::URL)->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItCannotUpdateRealNameWhenEventSaySo(): void
    {
        $this->event_manager->disable_real_name_change = true;

        $this->user_manager->shouldNotReceive('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesRealNameWhenRealNameChanged(): void
    {
        $this->user_manager->shouldReceive('updateDb')->withArgs(static function (\PFUser $user) {
            return $user->getRealName() === 'Franck Zappa';
        })->once()->andReturnTrue();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('realname', 'Franck Zappa')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(\Feedback::INFO, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('real name successfully updated', $feedback[0]['message']);
    }

    public function testItDoesntUpdateRealNameWhenNothingChanged(): void
    {
        $this->user_manager->shouldNotReceive('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('realname', 'Alice FooBar')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(\Feedback::INFO, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('nothing changed', $feedback[0]['message']);
    }

    public function testItThrowsAnErrorWhenRealNameIsTooLong(): void
    {
        $this->user_manager->shouldNotReceive('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('realname', 'FooBar Alice FooBar Alice FooBard')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(2, $feedback);
        $this->assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('too long', $feedback[0]['message']);
    }

    public function testItDoesntUpdateRealNameWhenDBUpdateFails(): void
    {
        $this->user_manager->shouldReceive('updateDb')->andReturnFalse();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('realname', 'Foo Bar')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(2, $feedback);
        $this->assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('real name was not updated', $feedback[0]['message']);
    }

    public function testItCannotUpdateEmailWhenEventSaySo(): void
    {
        $this->event_manager->disable_email_change = true;

        $this->email_updater->shouldNotReceive('setEmailChangeConfirm');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'bob@example.com')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesEmail(): void
    {
        $this->email_updater->shouldReceive('sendEmailChangeConfirm')->with(M::any(), $this->user)->once();
        $this->user_manager->shouldReceive('updateDb')->withArgs(static function (\PFUser $user) {
            return $user->getEmailNew() === 'bob@example.com' && $user->getConfirmHash() != '';
        })->once()->andReturnTrue();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'bob@example.com')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(\Feedback::INFO, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('email was successfully saved', $feedback[0]['message']);
    }

    public function testItUpdatesEmailButDbUpdateFails(): void
    {
        $this->email_updater->shouldNotReceive('sendEmailChangeConfirm');
        $this->user_manager->shouldReceive('updateDb')->andReturnFalse();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'bob@example.com')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(2, $feedback);
        $this->assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('email was not updated', $feedback[0]['message']);
    }

    public function testItUpdatesEmailWithoutUpdatingRealname(): void
    {
        $this->email_updater->shouldReceive('sendEmailChangeConfirm')->with(M::any(), $this->user)->once();
        $this->user_manager->shouldReceive('updateDb')->withArgs(static function (\PFUser $user) {
            return $user->getEmailNew() === 'bob@example.com';
        })->once()->andReturnTrue();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'bob@example.com')->withParam('realname', 'Alice FooBar')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(\Feedback::INFO, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('email was successfully saved', $feedback[0]['message']);
        $this->assertStringNotContainsStringIgnoringCase('nothing changed', $feedback[0]['message']);
    }

    public function testItDoesntUpdatesEmailWhenNoChanges(): void
    {
        $this->email_updater->shouldNotReceive('setEmailChangeConfirm');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'alice@example.com')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(\Feedback::INFO, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('nothing changed', $feedback[0]['message']);
    }

    public function testItReportsAnErrorWhenMailCannotBeSent(): void
    {
        $this->email_updater->shouldReceive('sendEmailChangeConfirm')->andThrow(new EmailNotSentException());
        $this->user_manager->shouldReceive('updateDb')->withArgs(static function (\PFUser $user) {
            return $user->getEmailNew() === 'bob@example.com';
        })->once()->andReturnTrue();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('email', 'bob@example.com')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('mail was not accepted for the delivery', $feedback[0]['message']);
    }

    public function testItDoesntUpdateWithInvalidTimezone(): void
    {
        $this->user_manager->shouldNotReceive('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('timezone', 'Frank Zappa')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(2, $feedback);
        $this->assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('invalid timezone', $feedback[0]['message']);
    }

    public function testItDoesntUpdateWithNoTimezoneChange(): void
    {
        $this->user_manager->shouldNotReceive('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('timezone', 'GMT')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(\Feedback::INFO, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('nothing changed', $feedback[0]['message']);
    }

    public function testItUpdatesTimezone(): void
    {
        $this->user_manager->shouldReceive('updateDb')->withArgs(static function (\PFUser $user) {
            return $user->getTimezone() === 'Europe/Berlin';
        })->once()->andReturnTrue();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('timezone', 'Europe/Berlin')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(\Feedback::INFO, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('timezone successfully updated', $feedback[0]['message']);
    }

    public function testItDoesntUpdateTimezoneWhenDBUpdateFails(): void
    {
        $this->user_manager->shouldReceive('updateDb')->andReturnFalse();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('timezone', 'Europe/Berlin')->build(),
            $this->layout,
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(2, $feedback);
        $this->assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('timezone was not updated', $feedback[0]['message']);
    }

    public function testItUpdatesEverythingAtOnce(): void
    {
        $this->email_updater->shouldReceive('sendEmailChangeConfirm')->with(M::any(), $this->user)->once();

        $this->user_manager->shouldReceive('updateDb')->withArgs(static function (\PFUser $user) {
            return $user->getRealName() === 'Franck Zappa';
        })->once()->andReturnTrue();
        $this->user_manager->shouldReceive('updateDb')->withArgs(static function (\PFUser $user) {
            return $user->getTimezone() === 'Europe/Berlin';
        })->once()->andReturnTrue();
        $this->user_manager->shouldReceive('updateDb')->withArgs(static function (\PFUser $user) {
            return $user->getEmailNew() === 'bob@example.com';
        })->once()->andReturnTrue();

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

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(3, $feedback);
        $this->assertEquals(\Feedback::INFO, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('real name successfully updated', $feedback[0]['message']);
        $this->assertEquals(\Feedback::INFO, $feedback[1]['level']);
        $this->assertStringContainsStringIgnoringCase('email was successfully saved', $feedback[1]['message']);
        $this->assertEquals(\Feedback::INFO, $feedback[2]['level']);
        $this->assertStringContainsStringIgnoringCase('timezone successfully updated', $feedback[2]['message']);
    }
}
