<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\Plugin\IsProjectAllowedToUsePlugin;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\ForbiddenException;

final class AdminPermissionsController extends DispatchablePSR15Compatible implements DispatchableWithBurningParrot
{
    public const string PROJECT_NAME_VARIABLE_NAME = 'project_name';

    public function __construct(
        private ResponseFactoryInterface $response_factory,
        private StreamFactoryInterface $stream_factory,
        private IsProjectAllowedToUsePlugin $plugin,
        private \TemplateRendererFactory $renderer_factory,
        private CSRFSynchronizerTokenProvider $token_provider,
        private AdminPermissionsPresenterBuilder $presenter_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $project = $request->getAttribute(\Project::class);
        assert($project instanceof \Project);

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        if (! $this->plugin->isAllowed($project->getID())) {
            throw new ForbiddenException();
        }

        $service = $project->getService(MediawikiStandaloneService::SERVICE_SHORTNAME);
        if (! $service instanceof MediawikiStandaloneService) {
            throw new ForbiddenException();
        }

        $layout = $request->getAttribute(BaseLayout::class);
        assert($layout instanceof BaseLayout);

        try {
            \ob_start();
            $service->displayAdministrationHeader($user);
            $this->renderer_factory
                ->getRenderer(__DIR__ . '/../../../templates')
                ->renderToPage(
                    'project-admin-permissions',
                    $this->presenter_builder->getPresenter(
                        $project,
                        self::getAdminUrl($project),
                        $this->token_provider->getCSRF($project)
                    )
                );
            $service->displayFooter();

            return $this->response_factory->createResponse()->withBody(
                $this->stream_factory->createStream((string) \ob_get_contents())
            );
        } finally {
            \ob_end_clean();
        }
    }

    public static function getAdminUrl(\Project $project): string
    {
        return '/mediawiki_standalone/admin/' . urlencode($project->getUnixNameMixedCase()) . '/permissions';
    }
}
