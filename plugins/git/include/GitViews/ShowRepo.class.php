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
     * @var Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager
     */
    private $gerrit_usermanager;

    /**
     * @var array
     */
    private $gerrit_servers;

    /** @var Git_GitRepositoryUrlManager */
    private $url_manager;

    public function __construct(
            GitRepository $repository,
            Git $controller,
            Git_GitRepositoryUrlManager $url_manager,
            Codendi_Request $request,
            Git_Driver_Gerrit $driver,
            Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
            array $gerrit_servers
    ) {
        $this->repository         = $repository;
        $this->controller         = $controller;
        $this->request            = $request;
        $this->driver             = $driver;
        $this->gerrit_usermanager = $gerrit_usermanager;
        $this->gerrit_servers     = $gerrit_servers;
        $this->url_manager        = $url_manager;
    }


    public function display() {
        $git_php_viewer = new GitViews_GitPhpViewer(
            $this->repository,
            $this->controller->getPlugin()->getConfigurationParameter('gitphp_path')
        );
        if ($this->request->get('noheader') == 1) {
            $view = new GitViews_ShowRepo_Download($git_php_viewer);
        } else {
            $view = new GitViews_ShowRepo_Content(
                $this->repository,
                $git_php_viewer,
                $this->request->getCurrentUser(),
                $this->controller,
                $this->url_manager,
                $this->driver,
                $this->gerrit_usermanager,
                $this->gerrit_servers,
                $this->controller->getPlugin()->getThemePath()
            );
        }
        $view->display();
    }
}

?>
