<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class GitViews_ShowRepo_Content {
    /**
     * @var GitRepository
     */
    protected $repository;

    /**
     * @var GitViews_GitPhpViewer
     */
    private $gitphp_viewer;

    /**
     * @var Git
     */
    private $controller;

    /**
     * @var string
     */
    private $theme_path;

    public function __construct(GitRepository $repository, GitViews_GitPhpViewer $gitphp_viewer, Git $controller, $theme_path) {
        $this->repository    = $repository;
        $this->gitphp_viewer = $gitphp_viewer;
        $this->controller    = $controller;
        $this->theme_path    = $theme_path;
    }

    public function display() {
        $repoId       = $this->repository->getId();
        $creator      = $this->repository->getCreator();
        $parent       = $this->repository->getParent();
        $access       = $this->repository->getAccess();
        $creatorName  = '';
        if ( !empty($creator) ) {
            $creatorName  = UserHelper::instance()->getLinkOnUserFromUserId($creator->getId());
        }
        $creationDate = html_time_ago(strtotime($this->repository->getCreationDate()));

        // Access type
        $accessType = $this->fetchAccessType($access, $this->repository->getBackend() instanceof Git_Backend_Gitolite);

        // Actions
        $repoActions = '<ul id="plugin_git_repository_actions">';
        if ($this->controller->isAPermittedAction('repo_management')) {
            $repoActions .= '<li><a href="/plugins/git/?action=repo_management&group_id='.$this->repository->getProjectId().'&repo_id='.$repoId.'" class="repo_admin"></li>';
        }

        $repoActions .= '</ul>';

        echo '<div id="plugin_git_reference">';
        echo '<h1>'.$accessType.$this->repository->getFullName().'</h1>';
        echo $repoActions;
        echo '<p id="plugin_git_reference_author">'.
            $GLOBALS['Language']->getText('plugin_git', 'view_repo_creator')
            .' '.
            $creatorName
            .' '.
            $creationDate
            .'</p>';
            ?>
    <?php
    if ( !empty($parent) ) :
    ?>
    <p id="plugin_git_repo_parent"><?php echo $GLOBALS['Language']->getText('plugin_git', 'view_repo_parent');
            ?>: <span><?php echo $this->_getRepositoryPageUrl( $parent->getId(), $parent->getName() );?></span>
    </p>
    <?php
    endif;

    ?>
    <p id="plugin_git_clone_url">
        <?php
        $hp = Codendi_HTMLPurifier::instance();
        $urls = $this->repository->getAccessURL();
        list(,$first_url) = each($urls);
        if (count($urls) > 1) {
            $selected = 'checked="checked"';
            foreach ($urls as $transport => $url) {
                echo '<label>';
                echo '<input type="radio" class="plugin_git_transport" name="plugin_git_transport" value="'. $hp->purify($url) .'" '.$selected.' />';
                echo $transport;
                echo '</label> ';
                $selected  = '';
            }
        }
        ?>
        <input id="plugin_git_clone_field" type="text" value="<?= $first_url; ?>" /></label>
    </p>
        <?php
        echo '</div>';
        echo $this->gitphp_viewer->getContent();
    }

    /**
     * Fetch the html code to display the icon of a repository (depends on type of project)
     *
     * @param $access
     * @param $backend_type
     */
    public function fetchAccessType($access, $backendIsGitolite) {
        $accessType = '<span class="plugin_git_repo_privacy" title=';

        if ($backendIsGitolite) {
            //$accessType .= '"'.$GLOBALS['Language']->getText('plugin_git', 'view_repo_access_custom').'">';
            $accessType .= '"custom">';
            $accessType .= '<img src="'.$this->theme_path.'/images/perms.png" />';
        } else {
            switch ($access) {
                case GitRepository::PRIVATE_ACCESS:
                    $accessType .= '"'.$GLOBALS['Language']->getText('plugin_git', 'view_repo_access_private').'">';
                    $accessType .= '<img src="'.util_get_image_theme('ic/lock.png').'" />';
                    break;
                case GitRepository::PUBLIC_ACCESS:
                    $accessType .= '"'.$GLOBALS['Language']->getText('plugin_git', 'view_repo_access_public').'">';
                    $accessType .= '<img src="'.util_get_image_theme('ic/lock-unlock.png').'" />';
                    break;
            }
        }
        $accessType .= '</span>';
        return $accessType;
    }


}

?>
