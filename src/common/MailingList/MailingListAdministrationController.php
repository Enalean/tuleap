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
use MailingListDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

class MailingListAdministrationController implements DispatchableWithBurningParrot, DispatchableWithRequest
{
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var MailingListDao
     */
    private $dao;
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var ProjectAdministratorChecker
     */
    private $administrator_checker;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        \TemplateRenderer $renderer,
        MailingListDao $dao
    ) {
        $this->renderer              = $renderer;
        $this->dao                   = $dao;
        $this->project_retriever     = $project_retriever;
        $this->administrator_checker = $administrator_checker;
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

        $mailing_list_presenters = [];
        foreach ($this->dao->searchByProject((int) $project->getID()) as $row) {
            $mailing_list_presenters[] = new MailingListPresenter(
                $row['list_name'],
                $row['description'],
                (bool) $row['is_public'],
                $this->getAdminUrl($request, $row['list_name']),
            );
        }

        $service->displayMailingListHeader($current_user, _('Mailing lists administration'));
        $this->renderer->renderToPage(
            'admin-index',
            new MailingListAdministrationPresenter(
                $mailing_list_presenters,
                '/mail/admin/?' . http_build_query(
                    [
                        'group_id' => $project->getID(),
                        'add_list' => 1,
                    ]
                )
            )
        );
        $service->displayFooter();
    }

    public static function getUrl(\Project $project): string
    {
        return '/project/' . urlencode((string) $project->getID()) . '/admin/mailing-lists';
    }

    public function getAdminUrl(HTTPRequest $request, string $list_name): string
    {
        $scheme = $request->isSecure() ? 'https://' : 'http://';

        return $scheme . \ForgeConfig::get('sys_lists_host') . '/mailman/admin/' . urlencode($list_name) . '/';
    }
}
