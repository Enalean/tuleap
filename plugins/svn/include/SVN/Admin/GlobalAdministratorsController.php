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

namespace Tuleap\SVN\Admin;

use HTTPRequest;
use Project;
use ProjectUGroup;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\SVN\ServiceSvn;
use Tuleap\SVN\SvnPermissionManager;
use User_ForgeUserGroupFactory;

class GlobalAdministratorsController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var User_ForgeUserGroupFactory
     */
    private $ugroup_factory;
    /**
     * @var SvnPermissionManager
     */
    private $permissions_manager;

    public function __construct(
        \ProjectManager $project_manager,
        User_ForgeUserGroupFactory $ugroup_factory,
        SvnPermissionManager $permissions_manager,
    ) {
        $this->project_manager     = $project_manager;
        $this->ugroup_factory      = $ugroup_factory;
        $this->permissions_manager = $permissions_manager;
    }

    public static function getURL(Project $project): string
    {
        return SVN_BASE_URL . "/" . urlencode((string) $project->getUnixNameMixedCase()) . "/admin";
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
        $service = $project->getService(\SvnPlugin::SERVICE_SHORTNAME);
        if (! ($service instanceof ServiceSvn)) {
            throw new NotFoundException(dgettext("tuleap-svn", "Unable to find SVN service"));
        }

        if (! $this->permissions_manager->isAdmin($project, $request->getCurrentUser())) {
            throw new ForbiddenException();
        }

        $request->set('group_id', $project->getID());
        $token = GlobalAdministratorsUpdater::generateToken($project);

        $service->renderInPage(
            $request,
            _('Administration'),
            'global-admin/admin_groups',
            new AdminGroupsPresenter(
                $project,
                $token,
                $this->getOptions($project),
            )
        );
    }

    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext("tuleap-svn", "Project not found."));
        }

        if (! $project->usesService(\SvnPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(
                sprintf(
                    dgettext("tuleap-svn", "SVN service is not activated in project %s"),
                    $project->getPublicName()
                )
            );
        }

        return $project;
    }

    /**
     * @psalm-return list<array{id: int, name: string, selected: bool}>
     */
    private function getOptions(Project $project): array
    {
        $options         = [];
        $project_ugroups = $this->ugroup_factory->getAllForProject($project);
        $svn_ugroups     = $this->permissions_manager->getAdminUgroupIds($project);

        foreach ($project_ugroups as $project_ugroup) {
            if ($project_ugroup->getId() == ProjectUGroup::ANONYMOUS) {
                continue;
            }

            $options[] = [
                'id'       => $project_ugroup->getId(),
                'name'     => $project_ugroup->getName(),
                'selected' => in_array($project_ugroup->getId(), $svn_ugroups),
            ];
        }

        return $options;
    }
}
