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

class GitViews_GitPhpViewer {
    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $gitphp_path;

    public function __construct(GitRepository $repository, $gitphp_path) {
        $this->repository  = $repository;
        $this->gitphp_path = $gitphp_path;
    }

    public function getContent() {
        ob_start();
        $this->getView();
        return ob_get_clean();
    }

    private function getView() {
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
            echo '<div id="gitphp" class="plugin_git_gitphp">';
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
        $gitphp_path = $this->gitphp_path;
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
