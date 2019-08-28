<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Routing;

use HTTPRequest;
use TemplateRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class TaskboardController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    /**
     * @var MilestoneExtractor
     */
    private $milestone_extractor;
    /**
     * @var TemplateRenderer
     */
    private $renderer;

    public function __construct(
        MilestoneExtractor $milestone_extractor,
        TemplateRenderer $renderer
    ) {
        $this->milestone_extractor = $milestone_extractor;
        $this->renderer            = $renderer;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        \Tuleap\Project\ServiceInstrumentation::increment(\taskboardPlugin::NAME);

        $milestone = $this->milestone_extractor->getMilestone($request->getCurrentUser(), $variables);

        $layout->header(
            [
                'title'  => $milestone->getArtifactTitle() . ' - ' . dgettext('tuleap-taskboard', "Taskboard"),
                'group'  => $milestone->getProject()->getID(),
                'toptab' => 'agiledashboard'
            ]
        );
        $this->renderer->renderToPage('taskboard', []);
        $layout->footer([]);
    }
}
