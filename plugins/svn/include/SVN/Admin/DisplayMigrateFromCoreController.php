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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN\Admin;

use CSRFSynchronizerToken;
use HTTPRequest;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\SVN\Repository\CoreRepository;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\ServiceSvn;
use Tuleap\SVN\SvnPermissionManager;

final class DisplayMigrateFromCoreController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var SvnPermissionManager
     */
    private $permissions_manager;
    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    public function __construct(
        \ProjectManager $project_manager,
        SvnPermissionManager $permissions_manager,
        RepositoryManager $repository_manager,
    ) {
        $this->project_manager     = $project_manager;
        $this->permissions_manager = $permissions_manager;
        $this->repository_manager  = $repository_manager;
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

        try {
            $repository = $this->repository_manager->getCoreRepository($project);
        } catch (CannotFindRepositoryException $exception) {
            $repository = CoreRepository::buildToBeCreatedRepository($project);
        }

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeAssets(
                    __DIR__ . '/../../../scripts/main/frontend-assets',
                    '/assets/svn/main'
                ),
                'global-admin-migrate.js'
            )
        );

        $service->renderInPage(
            $request,
            _('Administration'),
            'global-admin/migrate-from-core',
            new MigrateFromCorePresenter(
                $project,
                self::generateToken($project),
                $project->usesSVN(),
                $repository
            )
        );
    }

    public static function getURL(Project $project): string
    {
        return SVN_BASE_URL . "/" . urlencode((string) $project->getUnixNameMixedCase()) . "/admin-migrate";
    }

    public static function generateToken(Project $project): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::getURL($project));
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
}
