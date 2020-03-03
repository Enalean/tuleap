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
use Tuleap\User\Account\AccountInformationPreUpdateEvent;
use Tuleap\User\Account\DisplayAccountInformationController;
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

    protected function setUp(): void
    {
        $this->event_manager = new class implements EventDispatcherInterface {
            public $disable_real_name_change = false;

            public function dispatch(object $event)
            {
                if ($event instanceof AccountInformationPreUpdateEvent) {
                    if ($this->disable_real_name_change) {
                        $event->disableChangeRealName();
                    }
                }
                return $event;
            }
        };

        $this->csrf_token = M::mock(CSRFSynchronizerToken::class);
        $this->csrf_token->shouldReceive('check')->byDefault();

        $this->user_manager = M::mock(UserManager::class);
        $this->controller = new UpdateAccountInformationController(
            $this->event_manager,
            $this->csrf_token,
            $this->user_manager,
        );

        $this->layout_inspector = new LayoutInspector();

        $this->layout = LayoutBuilder::buildWithInspector($this->layout_inspector);

        $this->user = UserTestBuilder::aUser()
            ->withId(110)
            ->withRealName('Alice FooBar')
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
        })->once();

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
        $this->assertCount(1, $feedback);
        $this->assertEquals(\Feedback::ERROR, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('too long', $feedback[0]['message']);
    }
}
