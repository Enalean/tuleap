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
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

class MailingListUpdateController implements DispatchableWithRequest
{
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var ProjectAdministratorChecker
     */
    private $administrator_checker;
    /**
     * @var MailingListDao
     */
    private $dao;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        MailingListDao $dao,
    ) {
        $this->project_retriever     = $project_retriever;
        $this->administrator_checker = $administrator_checker;
        $this->dao                   = $dao;
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

        MailingListAdministrationController::getCSRF($project)->check();

        if (
            $this->dao->updateListInProject(
                (int) $variables['list-id'],
                (int) $project->getID(),
                $request->get('description'),
                (bool) $request->get('is_public')
            )
        ) {
            $layout->addFeedback(
                \Feedback::INFO,
                ('Mailing list has been successfully updated'),
            );
        } else {
            $layout->addFeedback(
                \Feedback::ERROR,
                ('An error occurred while updating the mailing list'),
            );
        }

        $layout->redirect(MailingListAdministrationController::getUrl($project));
    }
}
