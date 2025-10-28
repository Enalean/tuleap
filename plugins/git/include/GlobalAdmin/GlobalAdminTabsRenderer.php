<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Git\GlobalAdmin;

use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;

final readonly class GlobalAdminTabsRenderer
{
    public function __construct(
        private TemplateRenderer $template_renderer,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function renderTabs(Project $project, string $current_tab_name): void
    {
        $event = new GitAdminGetExternalPanePresenters($project, $current_tab_name);
        $this->event_dispatcher->dispatch($event);

        $presenter = new GlobalAdminTabsPresenter((int) $project->getID(), $event->getExternalPanePresenters());
        $this->template_renderer->renderToPage('git-administration-panes', $presenter);
    }
}
