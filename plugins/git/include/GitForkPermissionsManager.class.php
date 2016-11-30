<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;

/**
 * GitForkPermissionsManager
 */
class GitForkPermissionsManager {

    /**
     * @var GitPermissionsManager
     */
    private $git_permission_manager;

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $default_fine_grained_factory;

    /**
     * @var FineGrainedRepresentationBuilder
     */
    private $fine_grained_builder;

    /**
     * @var FineGrainedPermissionFactory
     */
    private $fine_grained_factory;

    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    /**
     * @var AccessRightsPresenterOptionsBuilder
     */
    private $builder;

    /** @var GitRepository */
    private $repository;
    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    public function __construct(
        GitRepository $repository,
        AccessRightsPresenterOptionsBuilder $builder,
        FineGrainedRetriever $fine_grained_retriever,
        FineGrainedPermissionFactory $fine_grained_factory,
        FineGrainedRepresentationBuilder $fine_grained_builder,
        DefaultFineGrainedPermissionFactory $default_fine_grained_factory,
        GitPermissionsManager $git_permission_manager,
        RegexpFineGrainedRetriever $regexp_retriever
    ) {
        $this->repository                   = $repository;
        $this->builder                      = $builder;
        $this->fine_grained_retriever       = $fine_grained_retriever;
        $this->fine_grained_factory         = $fine_grained_factory;
        $this->fine_grained_builder         = $fine_grained_builder;
        $this->default_fine_grained_factory = $default_fine_grained_factory;
        $this->git_permission_manager       = $git_permission_manager;
        $this->regexp_retriever             = $regexp_retriever;
    }

    /**
     * Wrapper
     *
     * @return ProjectManager
     */
    function getProjectManager() {
        return $this->repository->_getProjectManager();
    }

    /**
     * Wrapper
     *
     * @return Codendi_HTMLPurifier
     */
    function getPurifier() {
        return Codendi_HTMLPurifier::instance();
    }

    /**
     * Prepare fork destination message according to the fork scope
     *
     * @param Array $params Request params
     *
     * @return String
     */
    private function displayForkDestinationMessage($params) {
        if ($params['scope'] == 'project') {
            $project         = $this->getProjectManager()->getProject($params['group_id']);
            $destinationHTML = $GLOBALS['Language']->getText('plugin_git', 'fork_destination_project_message',  array($project->getPublicName()));
        } else {
            $destinationHTML = $GLOBALS['Language']->getText('plugin_git', 'fork_destination_personal_message');
        }
        return $destinationHTML;
    }

    /**
     * Prepare fork repository message
     *
     * @param String $repos Comma separated repositories Ids selected for fork
     *
     * @return String
     */
    private function displayForkSourceRepositories(array $repository_ids) {
        $dao             = new GitDao();
        $repoFactory     = new GitRepositoryFactory($dao, $this->getProjectManager());
        $sourceReposHTML = '';

        foreach ($repository_ids as $repositoryId) {
            $repository       = $repoFactory->getRepositoryById($repositoryId);
            $sourceReposHTML .= '"'.$this->getPurifier()->purify($repository->getFullName()).'" ';
        }
        return $sourceReposHTML;
    }

    /**
     * Fetch the html code to display permissions form when forking repositories
     *
     * @param Array   $params   Request params
     * @param Integer $groupId  Project Id
     * @param String  $userName User name
     *
     * @return String
     */
    public function displayRepositoriesPermissionsForm($params, $groupId, $userName) {
        $repository_ids  = explode(',', $params['repos']);
        $sourceReposHTML = $this->displayForkSourceRepositories($repository_ids);
        $form  = '<h2>'.$GLOBALS['Language']->getText('plugin_git', 'fork_repositories').'</h2>';
        $form .= $GLOBALS['Language']->getText('plugin_git', 'fork_repository_message', array($sourceReposHTML));
        $form .= $this->displayForkDestinationMessage($params);
        $form .= '<h3>Set permissions for the repository to be created</h3>';
        $form .= '<form action="" method="POST">';
        $form .= '<input type="hidden" name="group_id" value="'.(int)$groupId.'" />';
        $form .= '<input type="hidden" name="action" value="do_fork_repositories" />';
        $token = new CSRFSynchronizerToken('/plugins/git/?group_id='.(int)$groupId.'&action=fork_repositories');
        $form .= $token->fetchHTMLInput();
        $form .= '<input id="fork_repositories_repo" type="hidden" name="repos" value="'.$this->getPurifier()->purify($params['repos']).'" />';
        $form .= '<input id="choose_personal" type="hidden" name="choose_destination" value="'.$this->getPurifier()->purify($params['scope']).'" />';
        $form .= '<input id="to_project" type="hidden" name="to_project" value="'.$this->getPurifier()->purify($params['group_id']).'" />';
        $form .= '<input type="hidden" id="fork_repositories_path" name="path" value="'.$this->getPurifier()->purify($params['namespace']).'" />';
        $form .= '<input type="hidden" id="fork_repositories_prefix" value="u/'. $userName .'" />';
        if (count($repository_ids) > 1) {
            $form .= $this->displayDefaultAccessControl($groupId);
        } else {
            $form .= $this->displayAccessControlWhileForkingASingleRepository($groupId);
        }
        $form .= '<input type="submit" class="btn btn-primary" value="'.$GLOBALS['Language']->getText('plugin_git', 'fork_repositories').'" />';
        $form .= '</form>';
        return $form;
    }

    private function displayDefaultAccessControl($project_id) {
        $project = ProjectManager::instance()->getProject($project_id);
        $user    = UserManager::instance()->getCurrentUser();

        $can_use_fine_grained_permissions     = $this->git_permission_manager->userIsGitAdmin($user, $project);
        $are_fine_grained_permissions_defined = $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions($project);

        $branches_permissions = $this->default_fine_grained_factory->getBranchesFineGrainedPermissionsForProject($project);
        $tags_permissions     = $this->default_fine_grained_factory->getTagsFineGrainedPermissionsForProject($project);

        $branches_permissions_representation = array();
        foreach ($branches_permissions as $permission) {
            $branches_permissions_representation[] = $this->fine_grained_builder->buildDefaultPermission(
                $permission,
                $project
            );
        }

        $tags_permissions_representation = array();
        foreach ($tags_permissions as $permission) {
            $tags_permissions_representation[] = $this->fine_grained_builder->buildDefaultPermission(
                $permission,
                $project
            );
        }

        $new_fine_grained_ugroups = $this->getAllOptions($project);

        $delete_url = '?action=delete-permissions&pane=perms&repo_id='.$this->repository->getId().'&group_id='.$project->getID();
        $url        = '?action=repo_management&pane=perms&group_id='.$project->getID();
        $csrf       = new CSRFSynchronizerToken($url);
        $is_fork    = true;

        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');
        $presenter = new GitPresenters_AccessControlPresenter(
            $this->isRWPlusBlocked(),
            'repo_access['.Git::PERM_READ.']',
            'repo_access['.Git::PERM_WRITE.']',
            'repo_access['.Git::PERM_WPLUS.']',
            $this->getDefaultOptions($project, Git::DEFAULT_PERM_READ),
            $this->getDefaultOptions($project, Git::DEFAULT_PERM_WRITE),
            $this->getDefaultOptions($project, Git::DEFAULT_PERM_WPLUS),
            $are_fine_grained_permissions_defined,
            $can_use_fine_grained_permissions,
            $branches_permissions_representation,
            $tags_permissions_representation,
            $new_fine_grained_ugroups,
            $delete_url,
            $csrf,
            $is_fork,
            $this->areRegexpActivatedAtSiteLevel(),
            false,
            ""
        );

        return $renderer->renderToString('access-control', $presenter);
    }

    private function displayAccessControlWhileForkingASingleRepository($project_id)
    {
        return $this->displayAccessControlForm($project_id, true);
    }

    public function displayAccessControl($project_id = null)
    {
        return $this->displayAccessControlForm($project_id, false);
    }

    /**
     * Display access control management for gitolite backend
     *
     * @param Integer $project_id Project Id, to manage permissions when performing a cross project fork
     *
     * @return String
     */
    private function displayAccessControlForm($project_id = null, $is_fork = false) {
        $project = ($project_id) ? ProjectManager::instance()->getProject($project_id) : $this->repository->getProject();
        $user    = UserManager::instance()->getCurrentUser();

        $can_use_fine_grained_permissions = $this->git_permission_manager->userIsGitAdmin($user, $project);

        $are_fine_grained_permissions_defined = $this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions(
            $this->repository
        );

        $branches_permissions = $this->fine_grained_factory->getBranchesFineGrainedPermissionsForRepository($this->repository);
        $tags_permissions     = $this->fine_grained_factory->getTagsFineGrainedPermissionsForRepository($this->repository);

        $delete_url = '?action=delete-permissions&pane=perms&repo_id='.$this->repository->getId().'&group_id='.$project->getID();
        $url        = '?action=repo_management&pane=perms&group_id='.$project->getID();
        $csrf       = new CSRFSynchronizerToken($url);
        $branches_permissions_representation = array();
        foreach ($branches_permissions as $permission) {
            $branches_permissions_representation[] = $this->fine_grained_builder->buildRepositoryPermission(
                $permission,
                $project
            );
        }

        $tags_permissions_representation = array();
        foreach ($tags_permissions as $permission) {
            $tags_permissions_representation[] = $this->fine_grained_builder->buildRepositoryPermission(
                $permission,
                $project
            );
        }

        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');
        $presenter = new GitPresenters_AccessControlPresenter(
            $this->isRWPlusBlocked(),
            'repo_access['.Git::PERM_READ.']',
            'repo_access['.Git::PERM_WRITE.']',
            'repo_access['.Git::PERM_WPLUS.']',
            $this->getOptions($project, Git::PERM_READ),
            $this->getOptions($project, Git::PERM_WRITE),
            $this->getOptions($project, Git::PERM_WPLUS),
            $are_fine_grained_permissions_defined,
            $can_use_fine_grained_permissions,
            $branches_permissions_representation,
            $tags_permissions_representation,
            $this->getAllOptions($project),
            $delete_url,
            $csrf,
            $is_fork,
            $this->areRegexpActivatedAtSiteLevel(),
            $this->isRegexpActivatedForRepository(),
            $this->getWarningContentForRegexpDisableModal($this->repository)
        );

        return $renderer->renderToString('access-control', $presenter);
    }

    private function isRegexpActivatedForRepository()
    {
        return $this->regexp_retriever->areRegexpActivatedForRepository($this->repository);
    }

    private function isRWPlusBlocked() {
        $project_creator_status = new Git_Driver_Gerrit_ProjectCreatorStatus(
            new Git_Driver_Gerrit_ProjectCreatorStatusDao()
        );
        return ! $project_creator_status->canModifyPermissionsTuleapSide($this->repository);
    }

    private function getOptions(Project $project, $permission)
    {
        return $this->builder->getOptions($project, $this->repository, $permission);
    }

    private function getDefaultOptions(Project $project, $permission)
    {
        return $this->builder->getDefaultOptions($project, $permission);
    }

    private function getAllOptions(Project $project)
    {
        return $this->builder->getAllOptions($project);
    }

    private function areRegexpActivatedAtSiteLevel()
    {
        return $this->regexp_retriever->areRegexpActivatedAtSiteLevel();
    }

    private function getWarningContentForRegexpDisableModal(GitRepository $repository)
    {
        if ($this->regexp_retriever->areRegexpRepositoryConflitingWithPlateform($repository)) {
            $warning[]['message'] = $GLOBALS['Language']->getText(
                'plugin_git',
                'warning_conflit_regexp_configuration'
            );
            $warning[]['message'] = $GLOBALS['Language']->getText(
                'plugin_git',
                'warning_conflit_regexp_configuration_part_two'
            );
            $warning[]['message'] = $GLOBALS['Language']->getText(
                'plugin_git',
                'warning_conflit_regexp_configuration_confirm'
            );
        } else {
            $warning[]['message'] = $GLOBALS['Language']->getText(
                'plugin_git',
                'warning_regexp_uncheck'
            );
        }

        return $warning;
    }
}
