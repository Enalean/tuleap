<?php
/**
 * Copyright (c) Enalean, 2013 - 2016. All Rights Reserved.
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


use Tuleap\Git\History\GitPhpAccessLogger;

class GitViews_ShowRepo {
    /**
     * @var GitRepository
     */
    protected $repository;

    /**
     * @var Codendi_Request
     */
    protected $request;

    /**
     * @var Git_Driver_Gerrit
     */
    private $driver_factory;

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

    /** @var Git_Mirror_MirrorDataMapper */
    private $mirror_data_mapper;
    /**
     * @var GitPermissionsManager
     */
    private $permissions_manager;
    private $gitphp_path;
    private $master_location_name;

    public function __construct(
        GitRepository $repository,
        Git_GitRepositoryUrlManager $url_manager,
        Codendi_Request $request,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
        array $gerrit_servers,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        GitPhpAccessLogger $access_loger,
        GitPermissionsManager $permissions_manager,
        $gitphp_path,
        $master_location_name
    ) {
        $this->repository         = $repository;
        $this->request            = $request;
        $this->driver_factory     = $driver_factory;
        $this->gerrit_usermanager = $gerrit_usermanager;
        $this->gerrit_servers     = $gerrit_servers;
        $this->url_manager        = $url_manager;
        $this->mirror_data_mapper = $mirror_data_mapper;
        $this->access_loger       = $access_loger;
        $this->permissions_manager  = $permissions_manager;
        $this->gitphp_path          = $gitphp_path;
        $this->master_location_name = $master_location_name;
    }

    public function display(Git_URL $url) {
        $git_php_viewer = new GitViews_GitPhpViewer(
            $this->repository,
            $this->gitphp_path
        );
        if ($url->isADownload($this->request)) {
            $view = new GitViews_ShowRepo_Download($git_php_viewer);
        } else {
            $view = new GitViews_ShowRepo_Content(
                $this->repository,
                $git_php_viewer,
                $this->request,
                $this->request->getCurrentUser(),
                $this->url_manager,
                $this->driver_factory,
                $this->gerrit_usermanager,
                $this->mirror_data_mapper,
                $this->access_loger,
                $this->permissions_manager,
                $this->gerrit_servers,
                $this->master_location_name
            );
        }
        $view->display();
    }
}
