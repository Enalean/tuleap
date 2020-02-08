<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git;

use EventManager;
use GitPlugin;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class GitPluginDefaultController implements DispatchableWithRequest
{

    /**
     * @var \Tuleap\Git\RouterLink
     */
    private $router_link;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(RouterLink $router_link, EventManager $event_manager)
    {
        $this->router_link        = $router_link;
        $this->event_manager      = $event_manager;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     *
     * @return void
     * @throws \Tuleap\Request\NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getProject()->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new \Tuleap\Request\NotFoundException(dgettext("tuleap-git", "Git service is disabled."));
        }

        \Tuleap\Project\ServiceInstrumentation::increment('git');

        $this->event_manager->processEvent(
            new GitAdditionalActionEvent($request)
        );

        $this->router_link->process($request);
    }
}
