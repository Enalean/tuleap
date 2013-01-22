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

require_once 'ShowRepo/Content.class.php';
require_once 'ShowRepo/Download.class.php';

class GitViews_ShowRepo {
    /**
     * @var GitRepository
     */
    protected $repository;

    /**
     *
     * @var Git
     */
    protected $controller;

    /**
     * @var Codendi_Request
     */
    protected $request;

    /**
     * @var Git_Driver_Gerrit
     */
    private $driver;

    /**
     * @var array
     */
    private $gerrit_servers;

    public function __construct(GitRepository $repository, Git $controller, Codendi_Request $request, Git_Driver_Gerrit $driver, array $gerrit_servers) {
        $this->repository     = $repository;
        $this->controller     = $controller;
        $this->request        = $request;
        $this->driver         = $driver;
        $this->gerrit_servers = $gerrit_servers;
    }


    public function display() {
        if ( $this->request->get('noheader') == 1 ) {
            $view = new GitViews_ShowRepo_Download($this->repository, $this->controller, $this->request, $this->driver, $this->gerrit_servers);
        } else {
            $view = new GitViews_ShowRepo_Content($this->repository, $this->controller, $this->request, $this->driver, $this->gerrit_servers);
        }
        $view->display();
    }

    protected function getGitPhpContent() {
        ob_start();
        $this->getView();
        return ob_get_clean();
    }

    /**
     * Configure gitphp output
     *
     */
    private function getView() {
        include_once 'common/include/Codendi_HTMLPurifier.class.php';
        if ( empty($_REQUEST['a']) )  {
            $_REQUEST['a'] = 'summary';
        } else if ($_REQUEST['a'] === 'blobdiff') {
            $this->inverseURLArgumentsForGitPhpDiff();
        }
        set_time_limit(300);
        $_GET['a'] = $_REQUEST['a'];
        $_REQUEST['group_id']      = $this->repository->getProjectId();
        $_REQUEST['repo_id']       = $this->repository->getId();
        $_REQUEST['repo_name']     = $this->repository->getFullName();
        $_GET['p']                 = $_REQUEST['repo_name'].'.git';
        $_REQUEST['repo_path']     = $this->repository->getPath();
        $_REQUEST['project_dir']   = $this->repository->getProject()->getUnixName();
        $_REQUEST['git_root_path'] = $this->repository->getGitRootPath();
        $_REQUEST['action']        = 'view';
        if ( empty($_REQUEST['noheader']) ) {
            //echo '<hr>';
            echo '<div id="gitphp">';
        }

        include($this->getGitPhpIndexPath());

        if ( empty($_REQUEST['noheader']) ) {
            echo '</div>';
        }
    }
    /**
     * inverse the source and destination params in the URL to match the Git PHP
     * template
     */
    private function inverseURLArgumentsForGitPhpDiff() {
        $old_src  = $_GET['h'];
        $old_dest = $_GET['hp'];

        $_GET['h']  = $old_dest;
        $_GET['hp'] = $old_src;
    }

    /**
     * Return path to GitPhp index file
     *
     * @return String
     */
    private function getGitPhpIndexPath() {
        $gitphp_path = $this->controller->getPlugin()->getConfigurationParameter('gitphp_path');
        if ($gitphp_path) {
            $this->initGitPhpEnvironement();
        } else {
            $gitphp_path = GIT_BASE_DIR .'/../gitphp';
        }
        return $gitphp_path.'/index.php';
    }

    private function initGitPhpEnvironement() {
        define('GITPHP_CONFIGDIR', GIT_BASE_DIR .'/../etc/');
        ini_set('include_path', '/usr/share/gitphp-tuleap:'.ini_get('include_path'));
    }

}

?>
