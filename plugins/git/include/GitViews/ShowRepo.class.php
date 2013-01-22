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
require_once 'GitPhpViewer.class.php';

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
        $git_php_viewer = new GitViews_GitPhpViewer(
            $this->repository,
            $this->controller->getPlugin()->getConfigurationParameter('gitphp_path')
        );
        return $git_php_viewer->getContent();
    }


}

?>
