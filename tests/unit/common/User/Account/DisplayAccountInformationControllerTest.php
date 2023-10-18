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

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Request\ForbiddenException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DisplayAccountInformationControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    /**
     * @var DisplayAccountInformationController
     */
    private $controller;
    /**
     * @var \PFUser
     */
    private $user;
    private EventDispatcherInterface $event_manager;

    protected function setUp(): void
    {
        $this->event_manager = new class implements EventDispatcherInterface {
            public $disable_real_name_change = false;
            public $disable_email_change     = false;
            public $add_ldap_extra_info      = false;

            public function dispatch(object $event)
            {
                if ($event instanceof AccountInformationCollection) {
                    if ($this->disable_real_name_change) {
                        $event->disableChangeRealName();
                    }
                    if ($this->disable_email_change) {
                        $event->disableChangeEmail();
                    }
                    if ($this->add_ldap_extra_info) {
                        $event->addInformation(new AccountInformationPresenter('Ldap Stuff', 'some value'));
                    }
                }
                return $event;
            }
        };

        $this->controller = new DisplayAccountInformationController(
            $this->event_manager,
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->createMock(CSRFSynchronizerToken::class)
        );

        $language = $this->createStub(\BaseLanguage::class);
        $language->method('getText')->willReturn('');

        $this->user = UserTestBuilder::aUser()
            ->withId(110)
            ->withUserName('alice')
            ->withRealName('Alice FooBar')
            ->withEmail('alice@example.com')
            ->withAddDate((new \DateTimeImmutable())->getTimestamp())
            ->withLanguage($language)
            ->withAvatarUrl("/path/to/avatar.png")
            ->build();
    }

    public function testItThrowExceptionForAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItRendersThePage(): void
    {
        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('Account information', $output);
        self::assertStringContainsString('Member since', $output);
        self::assertStringNotContainsString('Ldap Stuff', $output);
    }

    public function testItRendersThePageWithRealnameEditable(): void
    {
        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('value="Alice FooBar"', $output);
    }

    public function testItRendersThePageWithRealnameReadOnly(): void
    {
        $this->event_manager->disable_real_name_change = true;

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('<p>Alice FooBar</p>', $output);
        self::assertStringNotContainsString('value="Alice FooBar"', $output);
    }

    public function testItRendersThePageWithExtraInfoFromPlugin(): void
    {
        $this->event_manager->add_ldap_extra_info = true;

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('Ldap Stuff', $output);
    }

    public function testItRendersThePageWithEmailEditable(): void
    {
        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('name="email"', $output);
        self::assertStringContainsString('value="alice@example.com"', $output);
    }

    public function testItRendersThePageWithEmailReadOnly(): void
    {
        $this->event_manager->disable_email_change = true;
        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('<p>alice@example.com</p>', $output);
        self::assertStringNotContainsString('name="email"', $output);
        self::assertStringNotContainsString('value="alice@example.com"', $output);
    }
}
