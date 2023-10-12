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

namespace Tuleap\User\Profile;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProfileAsJSONForTooltipControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItDoesNotLeakUserInfoIfCurrentUserIsAnonymous(): void
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $emitter          = $this->createMock(EmitterInterface::class);
        $controller       = new ProfileAsJSONForTooltipController(
            new JSONResponseBuilder(
                $response_factory,
                HTTPFactoryBuilder::streamFactory(),
            ),
            $emitter,
            $response_factory,
            $this->createMock(TemplateRendererFactory::class),
        );

        $user         = UserTestBuilder::anActiveUser()->build();
        $current_user = UserTestBuilder::anAnonymousUser()->build();

        $emitter->expects(self::atLeast(1))
            ->method('emit')
            ->with(
                self::callback(
                    static function (ResponseInterface $response): bool {
                        return $response->getStatusCode() === 403;
                    }
                )
            );

        $controller->process($current_user, $user);
    }

    public function testItSendTooltipInformationAsJson(): void
    {
        $response_factory          = HTTPFactoryBuilder::responseFactory();
        $emitter                   = $this->createMock(EmitterInterface::class);
        $template_renderer_factory = $this->createMock(TemplateRendererFactory::class);
        $controller                = new ProfileAsJSONForTooltipController(
            new JSONResponseBuilder(
                $response_factory,
                HTTPFactoryBuilder::streamFactory(),
            ),
            $emitter,
            $response_factory,
            $template_renderer_factory,
        );

        $user = UserTestBuilder::aUser()
            ->withId(164)
            ->withEmail('cystiferous@example.com')
            ->withAvatarUrl('/avatar/fc0c1a.png')
            ->withUserName('jvis')
            ->withRealName('Jacklyn Vis')
            ->build();

        $current_user = UserTestBuilder::anActiveUser()->build();

        $renderer = $this->createMock(TemplateRenderer::class);
        $template_renderer_factory
            ->method('getRenderer')
            ->willReturn($renderer);

        $renderer
            ->method('renderToString')
            ->willReturnCallback(function (string $template_name, mixed $presenter): string {
                if ($template_name === 'tooltip-title' && $presenter instanceof UserTooltipTitlePresenter) {
                    return 'title';
                } elseif ($template_name === 'tooltip-body' && $presenter instanceof UserTooltipBodyPresenter) {
                    return 'body';
                } else {
                    throw new \LogicException('must not be here');
                }
            });

        $emitter->expects(self::atLeast(1))
            ->method('emit')
            ->with(
                self::callback(
                    static function (ResponseInterface $response): bool {
                        return $response->getStatusCode() === 200
                            && $response->getBody()->getContents() === '{"title_as_html":"title","body_as_html":"body","accent_color":""}';
                    }
                )
            );

        $controller->process($current_user, $user);
    }
}
