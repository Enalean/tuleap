<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\BotMattermost\Administration\Project;

use HTTPRequest;
use Override;
use TemplateRenderer;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\Routing\LayoutHelper;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class ListBotController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private BotFactory $bot_factory,
        private LayoutHelper $layout_helper,
        private TemplateRenderer $renderer,
        private IncludeAssets $assets,
    ) {
    }

    #[Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user       = $request->getCurrentUser();
        $project_id = $variables['project_id'];

        if (! $user->isAdmin($project_id)) {
            throw new ForbiddenException(
                'User is not project administrator.'
            );
        }

        $callback = function (\Project $project, \PFUser $user): void {
            $project_id   = (int) $project->getID();
            $project_bots = $this->bot_factory->getProjectBots($project_id);

            $this->renderer->renderToPage(
                'admin',
                new ProjectAdministrationPresenter(
                    $project_bots,
                    new \CSRFSynchronizerToken(
                        "/plugins/botmattermost/project/$project_id/admin"
                    ),
                    $project_id
                )
            );
        };

        $layout->includeFooterJavascriptFile($this->assets->getFileURL('project-admin-modals.js'));
        $this->layout_helper->renderInProjectAdministrationLayout(
            $request,
            $project_id,
            dgettext('tuleap-botmattermost', 'Bots Mattermost'),
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME,
            $callback
        );
    }
}
