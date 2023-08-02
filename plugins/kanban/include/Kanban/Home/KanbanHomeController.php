<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\Home;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PFUser;
use Project;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\KanbanItemDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\NotFoundException;

final class KanbanHomeController extends DispatchablePSR15Compatible implements DispatchableWithBurningParrot
{
    public function __construct(
        private readonly ResponseFactoryInterface $response_factory,
        private readonly StreamFactoryInterface $stream_factory,
        private readonly KanbanFactory $kanban_factory,
        private readonly KanbanItemDao $kanban_item_dao,
        private readonly \TemplateRendererFactory $renderer_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute(PFUser::class);
        assert($user instanceof PFUser);

        $project = $request->getAttribute(Project::class);
        assert($project instanceof Project);

        $layout = $request->getAttribute(BaseLayout::class);
        assert($layout instanceof BaseLayout);

        $service = $project->getService('plugin_agiledashboard');
        if (! $service) {
            throw new NotFoundException();
        }

        ob_start();
        $layout->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptViteAsset(
                new \Tuleap\Layout\IncludeViteAssets(
                    __DIR__ . '/../../../scripts/kanban-homepage/frontend-assets/',
                    '/assets/kanban/kanban-homepage'
                ),
                'src/index.ts'
            )
        );
        $service->displayHeader(
            dgettext('tuleap-kanban', 'Kanban'),
            [],
            [],
            [],
        );
        $presenter = new KanbanHomePresenter(
            $this->getKanbanSummaryPresenters($user, $project),
            $user->isAdmin((int) $project->getID()),
        );
        $this->renderer_factory
            ->getRenderer(__DIR__ . '/../../../templates/')
            ->renderToPage('kanban-homepage', $presenter);
        $service->displayFooter();

        return $this->response_factory->createResponse()->withBody(
            $this->stream_factory->createStream((string) ob_get_clean())
        );
    }

    /**
     * @return KanbanSummaryPresenter[]
     */
    private function getKanbanSummaryPresenters(PFUser $user, Project $project): array
    {
        $kanban_presenters = [];

        $list_of_kanban = $this->kanban_factory->getListOfKanbansForProject(
            $user,
            (int) $project->getID(),
        );

        foreach ($list_of_kanban as $kanban_for_project) {
            $kanban_presenters[] = new KanbanSummaryPresenter($kanban_for_project, $this->kanban_item_dao);
        }

        return $kanban_presenters;
    }

    public static function getHomeUrl(Project $project): string
    {
        return '/plugins/agiledashboard/?' . http_build_query(['group_id' => $project->getID()]);
    }
}
