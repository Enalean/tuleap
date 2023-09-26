<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitViews;

use EventManager;
use HTTPRequest;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;

class GitViewHeader
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        EventManager $event_manager,
    ) {
        $this->event_manager = $event_manager;
    }

    public function header(
        HTTPRequest $request,
        BaseLayout $layout,
        Project $project,
        BreadCrumbCollection $breadcrumbs,
    ) {
        $layout->addBreadcrumbs($breadcrumbs);

        $layout->header(
            [
                'title'      => dgettext('tuleap-git', 'Git'),
                'project'    => $project,
                'toptab'     => dgettext('tuleap-git', 'Git'),
                'body_class' => array_merge(['git-administration'], $this->getAdditionalBodyClasses($request)),
            ]
        );
    }

    private function getAdditionalBodyClasses(HTTPRequest $request)
    {
        $classes = [];
        $params  = [
            'request' => $request,
            'classes' => &$classes,
        ];

        $this->event_manager->processEvent(GIT_ADDITIONAL_BODY_CLASSES, $params);

        return $classes;
    }
}
