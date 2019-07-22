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
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ActivationController implements DispatchableWithRequest, DispatchableWithProject
{
    /** @var \ProjectManager */
    private $project_manager;
    /** @var SynchronizedProjectMembershipDao */
    private $dao;
    /** @var \CSRFSynchronizerToken */
    private $csrf;

    public function __construct(
        \ProjectManager $project_manager,
        SynchronizedProjectMembershipDao $dao,
        \CSRFSynchronizerToken $csrf
    ) {
        $this->project_manager = $project_manager;
        $this->dao             = $dao;
        $this->csrf            = $csrf;
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
     * @throws NotFoundException
     */
    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProject($variables['id']);
        if (! $project || $project->isError()) {
            throw new NotFoundException();
        }
        return $project;
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
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
