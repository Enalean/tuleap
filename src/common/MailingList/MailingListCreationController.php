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

namespace Tuleap\MailingList;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

class MailingListCreationController implements DispatchableWithBurningParrot, DispatchableWithRequest
{
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var ProjectAdministratorChecker
     */
    private $administrator_checker;
    /**
     * @var MailingListDomainBuilder
     */
    private $list_domain_builder;
    /**
     * @var MailingListCreationPresenterBuilder
     */
    private $presenter_builder;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        \TemplateRenderer $renderer,
        MailingListDomainBuilder $list_domain_builder,
        MailingListCreationPresenterBuilder $presenter_builder,
    ) {
        $this->renderer              = $renderer;
        $this->project_retriever     = $project_retriever;
        $this->administrator_checker = $administrator_checker;
        $this->list_domain_builder   = $list_domain_builder;
        $this->presenter_builder     = $presenter_builder;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->project_retriever->getProjectFromId($variables['id']);
        if (! $project->usesMail()) {
            throw new NotFoundException();
        }
        $service = $project->getService(\Service::ML);
        if (! ($service instanceof ServiceMailingList)) {
            throw new NotFoundException();
        }

        $current_user = $request->getCurrentUser();
        $this->administrator_checker->checkUserIsProjectAdministrator($current_user, $project);

        $sys_lists_domain = $this->list_domain_builder->build();

        $presenter = $this->presenter_builder->build(
            $project,
            $current_user,
            MailingListAdministrationController::getCSRF($project),
            $sys_lists_domain,
            $this->getIntro((int) $project->getID(), $sys_lists_domain),
        );

        $service->displayMailingListHeader($current_user, _('Add a mailing list'));
        $this->renderer->renderToPage('admin-add', $presenter);
        $service->displayFooter();
    }

    private function getIntro(int $group_id, string $sys_lists_domain): string
    {
        // Note: $group_id and $sys_lists_domain parameters are used inside addlist_intro.txt templates

        ob_start();
        include($GLOBALS['Language']->getContent('mail/addlist_intro'));

        return (string) ob_get_clean();
    }

    public static function getUrl(\Project $project): string
    {
        return '/project/' . urlencode((string) $project->getID()) . '/admin/mailing-lists/add';
    }
}
