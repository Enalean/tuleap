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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Date\DateHelper;
use Tuleap\GlobalLanguageMock;
use Tuleap\Request\ForbiddenException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\Account\Appearance\AppearancePresenterBuilder;
use Tuleap\User\Account\Appearance\AppearancePresenter;

final class DisplayAppearanceControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;
    use GlobalLanguageMock;

    private DisplayAppearanceController $controller;
    private MockObject&AppearancePresenterBuilder $appearance_builder;
    private CSRFSynchronizerToken&MockObject $csrf_token;

    public function setUp(): void
    {
        $event_manager = new class implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                return $event;
            }
        };

        $GLOBALS['Language']->method('gettext')->with('system', 'datefmt_short')->willReturn('d/m/Y');

        $this->appearance_builder = $this->createMock(AppearancePresenterBuilder::class);
        $this->csrf_token         = $this->createMock(CSRFSynchronizerToken::class);

        $this->controller = new DisplayAppearanceController(
            $event_manager,
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->csrf_token,
            $this->appearance_builder
        );
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

    public function testItRendersThePageWithAppearance(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $this->appearance_builder
            ->expects(self::once())
            ->method('getAppareancePresenterForUser')
            ->willReturn(
                new AppearancePresenter(
                    $this->csrf_token,
                    $this->createMock(AccountTabPresenterCollection::class),
                    [],
                    [],
                    true,
                    true,
                    true,
                    true,
                    true,
                    true,
                    DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN
                )
            );

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('Appearance & language', $output);
    }
}
