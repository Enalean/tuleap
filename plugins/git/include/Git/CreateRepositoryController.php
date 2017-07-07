<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Git;

use Exception;
use Git_Backend_Gitolite;
use Git_GitRepositoryUrlManager;
use Git_Mirror_MirrorDataMapper;
use GitPermissionsManager;
use GitRepositoryFactory;
use GitRepositoryManager;
use HTTPRequest;
use ProjectHistoryDao;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Valid_String;
use Tuleap\Git\CIToken\Manager as CITokenManager;

class CreateRepositoryController extends RouterLink
{
    /**
     * @var GitRepositoryFactory
     */
    private $factory;
    /**
     * @var Git_Backend_Gitolite
     */
    private $backend_gitolite;
    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;
    /**
     * @var GitRepositoryManager
     */
    private $manager;
    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var FineGrainedPermissionReplicator
     */
    private $fine_grained_replicator;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var HistoryValueFormatter
     */
    private $history_value_formatter;
    /**
     * @var CITokenManager
     */
    private $ci_token_manager;
    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $url_manager;

    public function __construct(
        GitRepositoryFactory $factory,
        Git_Backend_Gitolite $backend_gitolite,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        GitRepositoryManager $manager,
        GitPermissionsManager $git_permissions_manager,
        FineGrainedPermissionReplicator $fine_grained_replicator,
        ProjectHistoryDao $history_dao,
        HistoryValueFormatter $history_value_formatter,
        CITokenManager $ci_token_manager,
        Git_GitRepositoryUrlManager $url_manager
    ) {
        parent::__construct();
        $this->factory                 = $factory;
        $this->backend_gitolite        = $backend_gitolite;
        $this->mirror_data_mapper      = $mirror_data_mapper;
        $this->manager                 = $manager;
        $this->git_permissions_manager = $git_permissions_manager;
        $this->fine_grained_replicator = $fine_grained_replicator;
        $this->history_dao             = $history_dao;
        $this->history_value_formatter = $history_value_formatter;
        $this->ci_token_manager        = $ci_token_manager;
        $this->url_manager             = $url_manager;
    }

    public function process(HTTPRequest $request)
    {
        switch ($request->get('action')) {
            case 'add':
                $this->createRepository($request);
                break;
            default:
                parent::process($request);
                break;
        }
    }

    private function createRepository(HTTPRequest $request)
    {
        $repository_name = $this->getNameFromRequest($request);

        $creator    = $request->getCurrentUser();
        $project    = $request->getProject();
        $project_id = $project->getID();

        try {
            $repository = $this->factory->buildRepository(
                $project,
                $repository_name,
                $creator,
                $this->backend_gitolite
            );

            $default_mirrors = $this->mirror_data_mapper->getDefaultMirrorIdsForProject($project);
            if (! $default_mirrors) {
                $default_mirrors = array();
            }

            $this->manager->create($repository, $this->backend_gitolite, $default_mirrors);

            $this->backend_gitolite->savePermissions(
                $repository,
                $this->git_permissions_manager->getDefaultPermissions($project)
            );

            $this->fine_grained_replicator->replicateDefaultRegexpUsage($repository);
            $this->fine_grained_replicator->replicateDefaultPermissions(
                $repository
            );

            $this->history_dao->groupAddHistory(
                "git_repo_create",
                $repository_name,
                $project_id
            );

            $this->history_dao->groupAddHistory(
                'perm_granted_for_git_repository',
                $this->history_value_formatter->formatValueForRepository($repository),
                $project_id,
                array($repository_name)
            );

            $this->ci_token_manager->generateNewTokenForRepository($repository);

            $redirect_url = $this->url_manager->getRepositoryBaseUrl($repository);
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback('error', $exception->getMessage());
            $redirect_url = '/plugins/git/?action=index&group_id='. $project_id;
        }

        $GLOBALS['Response']->redirect($redirect_url);
    }

    /**
     * @return null|string
     */
    private function getNameFromRequest(HTTPRequest $request)
    {
        $valid = new Valid_String('repo_name');
        $valid->required();
        $repository_name = null;
        if ($request->valid($valid)) {
            $repository_name = trim($request->get('repo_name'));
        }
        return $repository_name;
    }
}
