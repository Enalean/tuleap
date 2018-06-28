<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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
    /** @var Git_Mirror_MirrorDataMapper */
    private $mirror_data_mapper;
    /**
     * @var GitPhpAccessLogger
     */
    private $access_logger;

    public function __construct(
        GitRepository $repository,
        HTTPRequest $request,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        GitPhpAccessLogger $access_logger
    ) {
        $this->repository         = $repository;
        $this->request            = $request;
        $this->mirror_data_mapper = $mirror_data_mapper;
        $this->access_logger      = $access_logger;
    }

    public function display(Git_URL $url) {
        $git_php_viewer = new GitViews_GitPhpViewer($this->repository);
        if ($url->isADownload($this->request)) {
            $view = new GitViews_ShowRepo_Download($git_php_viewer);
        } else {
            $view = new GitViews_ShowRepo_Content(
                $this->repository,
                $git_php_viewer,
                $this->request,
                $this->mirror_data_mapper,
                $this->access_logger
            );
        }
        $view->display();
    }
}
