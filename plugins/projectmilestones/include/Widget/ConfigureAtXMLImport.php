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

namespace Tuleap\ProjectMilestones\Widget;

use Tuleap\Widget\Event\ConfigureAtXMLImport as ConfigureAtXMLImportEvent;

final class ConfigureAtXMLImport
{
    public function __invoke(ConfigureAtXMLImportEvent $event): void
    {
        if ($event->getWidget()->getId() !== DashboardProjectMilestones::NAME) {
            return;
        }
        $content_id = $event->getWidget()->create(new \Codendi_Request([
            ProjectMilestonesWidgetRetriever::PARAM_SELECTED_PROJECT => ProjectMilestonesWidgetRetriever::VALUE_SELECTED_PROJECT_SELF,
            'project' => $event->getProject()
        ]));
        $event->setContentId($content_id);
        $event->setWidgetIsConfigured();
    }
}
