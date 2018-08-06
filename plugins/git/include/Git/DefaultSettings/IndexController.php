<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\DefaultSettings;

use CSRFSynchronizerToken;
use Git;
use Git_Mirror_MirrorDataMapper;
use GitPermissionsManager;
use GitPresenters_AdminDefaultSettingsPresenter;
use GitPresenters_MirrorPresenter;
use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use UserManager;

class IndexController
{
    const DEFAULT_SETTINGS_PANE_MIRRORING      = 'mirroring';
    const DEFAULT_SETTINGS_PANE_ACCESS_CONTROL = 'access_control';

    /**
     * @var AccessRightsPresenterOptionsBuilder
     */
    private $access_rights_builder;
    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;
    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $default_fine_grained_permission_factory;
    /**
     * @var FineGrainedRepresentationBuilder
     */
    private $fine_grained_builder;
    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;
    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;
    /**
     * @var HeaderRenderer
     */
    private $header_renderer;

    public function __construct(
        AccessRightsPresenterOptionsBuilder $access_rights_builder,
        GitPermissionsManager $git_permissions_manager,
        FineGrainedRetriever $fine_grained_retriever,
        DefaultFineGrainedPermissionFactory $default_fine_grained_permission_factory,
        FineGrainedRepresentationBuilder $fine_grained_builder,
        RegexpFineGrainedRetriever $regexp_retriever,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        HeaderRenderer $header_renderer
    ) {
        $this->access_rights_builder                   = $access_rights_builder;
        $this->git_permissions_manager                 = $git_permissions_manager;
        $this->fine_grained_retriever                  = $fine_grained_retriever;
        $this->default_fine_grained_permission_factory = $default_fine_grained_permission_factory;
        $this->fine_grained_builder                    = $fine_grained_builder;
        $this->regexp_retriever                        = $regexp_retriever;
        $this->mirror_data_mapper                      = $mirror_data_mapper;
        $this->header_renderer                         = $header_renderer;
    }

    public function displayDefaultSettings(HTTPRequest $request)
    {
        $project = $request->getProject();

        $pane = '';
        if ($request->exist('pane')) {
            $pane = $request->get('pane');
        }

        $mirror_presenters = $this->getMirrorPresentersForGitAdmin($project);
        $project_id        = $project->getID();

        $read_options   = $this->access_rights_builder->getDefaultOptions($project, Git::DEFAULT_PERM_READ);
        $write_options  = $this->access_rights_builder->getDefaultOptions($project, Git::DEFAULT_PERM_WRITE);
        $rewind_options = $this->access_rights_builder->getDefaultOptions($project, Git::DEFAULT_PERM_WPLUS);
        $csrf           = new CSRFSynchronizerToken(
            "plugins/git/?group_id=$project_id&action=admin-default-access-rights"
        );

        $pane_access_control = true;
        $pane_mirroring      = false;

        $are_mirrors_defined = $this->areMirrorsEnabledForProject($project);
        if ($are_mirrors_defined && $pane === self::DEFAULT_SETTINGS_PANE_MIRRORING) {
            $pane_access_control = false;
            $pane_mirroring      = true;
        }

        $user = UserManager::instance()->getCurrentUser();

        $can_use_fine_grained_permissions = $this->git_permissions_manager->userIsGitAdmin($user, $project);

        $are_fine_grained_permissions_defined = $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions(
            $project
        );

        $branches_permissions     = $this->default_fine_grained_permission_factory->getBranchesFineGrainedPermissionsForProject(
            $project
        );
        $tags_permissions         = $this->default_fine_grained_permission_factory->getTagsFineGrainedPermissionsForProject(
            $project
        );
        $new_fine_grained_ugroups = $this->access_rights_builder->getAllOptions($project);

        $delete_url  = '?action=delete-default-permissions&pane=access_control&group_id=' . $project->getID();
        $url         = '?action=admin-default-settings&pane=access_control&group_id=' . $project->getID();
        $csrf_delete = new CSRFSynchronizerToken($url);

        $branches_permissions_representation = [];
        foreach ($branches_permissions as $permission) {
            $branches_permissions_representation[] = $this->fine_grained_builder->buildDefaultPermission(
                $permission,
                $project
            );
        }

        $tags_permissions_representation = [];
        foreach ($tags_permissions as $permission) {
            $tags_permissions_representation[] = $this->fine_grained_builder->buildDefaultPermission(
                $permission,
                $project
            );
        }

        $presenter = new GitPresenters_AdminDefaultSettingsPresenter(
            $project_id,
            $are_mirrors_defined,
            $mirror_presenters,
            $csrf,
            $read_options,
            $write_options,
            $rewind_options,
            $pane_access_control,
            $pane_mirroring,
            $are_fine_grained_permissions_defined,
            $can_use_fine_grained_permissions,
            $branches_permissions_representation,
            $tags_permissions_representation,
            $new_fine_grained_ugroups,
            $delete_url,
            $csrf_delete,
            $this->areRegexpActivatedAtSiteLevel(),
            $this->isRegexpActivatedForDefault($project),
            $this->areRegexpConflictingForDefault($project)
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        $this->header_renderer->renderServiceAdministrationHeader($request, $request->getCurrentUser(), $request->getProject());
        echo $renderer->renderToString('admin', $presenter);
        $GLOBALS['HTML']->footer([]);
    }

    private function areRegexpActivatedAtSiteLevel()
    {
        return $this->regexp_retriever->areRegexpActivatedAtSiteLevel();
    }

    private function isRegexpActivatedForDefault(Project $project)
    {
        return $this->regexp_retriever->areRegexpActivatedForDefault($project);
    }

    private function areRegexpConflictingForDefault(Project $project)
    {
        return $this->regexp_retriever->areDefaultRegexpConflitingWithPlateform($project);
    }

    private function getMirrorPresentersForGitAdmin(Project $project)
    {
        $mirrors            = $this->mirror_data_mapper->fetchAllForProject($project);
        $default_mirror_ids = $this->mirror_data_mapper->getDefaultMirrorIdsForProject($project);
        $mirror_presenters  = [];

        foreach ($mirrors as $mirror) {
            $is_used = in_array($mirror->id, $default_mirror_ids);

            $mirror_presenters[] = new GitPresenters_MirrorPresenter($mirror, $is_used);
        }

        return $mirror_presenters;
    }

    private function areMirrorsEnabledForProject(Project $project)
    {
        return count($this->mirror_data_mapper->fetchAllForProject($project)) > 0;
    }
}
