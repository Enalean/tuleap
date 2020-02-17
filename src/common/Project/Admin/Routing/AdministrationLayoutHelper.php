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
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\Routing;

use Tuleap\Project\Admin\Navigation\FooterDisplayer;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Request\ProjectRetriever;

class AdministrationLayoutHelper implements LayoutHelper
{
    /** @var ProjectRetriever */
    private $project_retriever;
    /** @var ProjectAdministratorChecker */
    private $administrator_checker;
    /** @var HeaderNavigationDisplayer */
    private $header_displayer;
    /** @var FooterDisplayer */
    private $footer_displayer;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        HeaderNavigationDisplayer $header_displayer,
        FooterDisplayer $footer_displayer
    ) {
        $this->project_retriever     = $project_retriever;
        $this->administrator_checker = $administrator_checker;
        $this->header_displayer      = $header_displayer;
        $this->footer_displayer      = $footer_displayer;
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            new HeaderNavigationDisplayer(),
            new FooterDisplayer()
        );
    }

    /**
     * @psalm-param Closure(\Project, \PFUser):void $callback
     * @throws \Tuleap\Request\ForbiddenException
     * @throws \Tuleap\Request\NotFoundException
     */
    public function renderInProjectAdministrationLayout(
        \HTTPRequest $request,
        string $project_id,
        string $page_title,
        string $current_pane_shortname,
        \Closure $callback
    ): void {
        $project      = $this->project_retriever->getProjectFromId($project_id);
        $current_user = $request->getCurrentUser();
        $this->administrator_checker->checkUserIsProjectAdministrator($current_user, $project);

        $this->header_displayer->displayBurningParrotNavigation($page_title, $project, $current_pane_shortname);
        $callback($project, $current_user);
        $this->footer_displayer->display();
    }
}
