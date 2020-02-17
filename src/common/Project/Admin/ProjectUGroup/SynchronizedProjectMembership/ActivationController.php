<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup\SynchronizedProjectMembership;

use HTTPRequest;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\ProjectUGroup\UGroupRouter;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

class ActivationController implements DispatchableWithRequest
{
    /** @var ProjectRetriever */
    private $project_retriever;
    /** @var ProjectAdministratorChecker */
    private $administrator_checker;
    /** @var SynchronizedProjectMembershipDao */
    private $dao;
    /** @var \CSRFSynchronizerToken */
    private $csrf;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        SynchronizedProjectMembershipDao $dao,
        \CSRFSynchronizerToken $csrf
    ) {
        $this->project_retriever     = $project_retriever;
        $this->administrator_checker = $administrator_checker;
        $this->dao                   = $dao;
        $this->csrf                  = $csrf;
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            new SynchronizedProjectMembershipDao(),
            UGroupRouter::getCSRFTokenSynchronizer()
        );
    }

    public static function getUrl(Project $project): string
    {
        return sprintf(
            '/project/%s/admin/change-synchronized-project-membership',
            urlencode((string) $project->getID())
        );
    }

    private function getRedirectUrl(Project $project): string
    {
        return '/project/admin/ugroup.php?' . http_build_query(
            ['group_id' => $project->getID()]
        );
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->project_retriever->getProjectFromId($variables['id']);
        $this->administrator_checker->checkUserIsProjectAdministrator($request->getCurrentUser(), $project);
        $this->csrf->check($this->getRedirectUrl($project));

        $activation = $request->get('activation') === 'on';
        $this->toggleProjectMembership($project, $activation);

        $layout->redirect($this->getRedirectUrl($project));
    }

    private function toggleProjectMembership(Project $project, bool $activation): void
    {
        if ($activation) {
            $this->dao->enable($project);
            return;
        }
        $this->dao->disable($project);
    }
}
