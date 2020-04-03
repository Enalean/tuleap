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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Request\ForbiddenException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\User\Account\Appearance\AppareancePresenterBuilder;
use Tuleap\User\Account\Appearance\AppearancePresenter;

class DisplayAppearanceControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    /**
     * @var DisplayExperimentalController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AppareancePresenterBuilder
     */
    private $appearance_builder;
    /**
     * @var CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;

    public function setUp(): void
    {
        $event_manager = new class implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                return $event;
            }
        };

        $this->appearance_builder = M::mock(AppareancePresenterBuilder::class);
        $this->csrf_token = M::mock(CSRFSynchronizerToken::class);

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
        $user = M::mock(\PFUser::class);
        $user->shouldReceive(['useLabFeatures' => true, 'isAnonymous' => false]);

        $this->appearance_builder
            ->shouldReceive('getAppareancePresenterForUser')
            ->once()
            ->andReturn(
                new AppearancePresenter(
                    $this->csrf_token,
                    M::mock(AccountTabPresenterCollection::class),
                    [],
                    [],
                    true,
                    true,
                    true,
                    true,
                    true,
                    true
                )
            );

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        $this->assertStringContainsString('Appearance & language', $output);
    }
}
