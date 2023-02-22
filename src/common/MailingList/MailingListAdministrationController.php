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

use CSRFSynchronizerToken;
use HTTPRequest;
use MailingListDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\JavascriptAsset;
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
    /**
     * @var MailingListPresenterCollectionBuilder
     */
    private $presenter_collection_builder;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        \TemplateRenderer $renderer,
        MailingListDao $dao,
        MailingListPresenterCollectionBuilder $presenter_collection_builder,
    ) {
        $this->renderer                     = $renderer;
        $this->dao                          = $dao;
        $this->project_retriever            = $project_retriever;
        $this->administrator_checker        = $administrator_checker;
        $this->presenter_collection_builder = $presenter_collection_builder;
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

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new \Tuleap\Layout\IncludeCoreAssets(),
                'mailing-lists-administration.js'
            )
        );

        $mailing_list_presenters = $this->getMailingListPresenters($project, $request);

        $service->displayMailingListHeader($current_user, _('Mailing lists administration'));
        $this->renderer->renderToPage(
            'admin-index',
            new MailingListAdministrationPresenter(
                $mailing_list_presenters,
                MailingListCreationController::getUrl($project),
                self::getCSRF($project)
            )
        );
        $service->displayFooter();
    }

    public static function getCSRF(\Project $project): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken('/mail/?group_id=' . urlencode((string) $project->getID()));
    }

    public static function getUrl(\Project $project): string
    {
        return '/project/' . urlencode((string) $project->getID()) . '/admin/mailing-lists';
    }

    /**
     * @return MailingListPresenter[]
     */
    private function getMailingListPresenters(\Project $project, HTTPRequest $request): array
    {
        $data_access_result = $this->dao->searchActiveListsInProject((int) $project->getID());
        if (! $data_access_result) {
            return [];
        }

        return $this->presenter_collection_builder->build($data_access_result, $project, $request);
    }
}
