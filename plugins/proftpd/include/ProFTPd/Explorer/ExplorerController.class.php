<?php

/**
 * Copyright (c) Enalean, 2012. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\ProFTPd\Explorer;

use Tuleap\ProFTPd\Directory\DirectoryParser;
use Tuleap\ProFTPd\Directory\DirectoryPathParser;
use Tuleap\ProFTPd\Presenter\ExplorerPresenter;
use Tuleap\ProFTPd\ServiceProFTPd;
use Tuleap\ProFTPd\Admin\PermissionsManager;
use HTTPRequest;
use PFUser;
use Project;

class ExplorerController {
    const NAME = 'explorer';

    /** @var DirectoryParser */
    private $parser;

    /** @var PermissionsManager */
    private $permissions_manager;

    public function __construct(DirectoryParser $parser, PermissionsManager $permissions_manager) {
        $this->parser              = $parser;
        $this->permissions_manager = $permissions_manager;
    }

    public function getName() {
        return self::NAME;
    }

    public function index(ServiceProFTPd $service, HTTPRequest $request) {

        if ($this->userHasPermissionToExploreSFTP($request->getCurrentUser(), $request->getProject())) {
            $this->renderIndex($service, $request);
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_proftpd', 'error_browse_rights'));
            $service->renderInPage(
                $request,
                '',
                'index',
                null
            );
        }
    }

    private function renderIndex(ServiceProFTPd $service, HTTPRequest $request) {
        $path_parser = new DirectoryPathParser();

        $path        = $path_parser->getCleanPath($request->get('path'));
        $path_parts  = $path_parser->getPathParts($path);

        $project        = $request->getProject();
        if (! $project ) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_proftpd', 'cannot_open_project'));
            return;
        }

        $remove_parent_directory_listing = ($path == '') ? true : false;
        $items = $this->parser->parseDirectory($project->getUnixName() . DIRECTORY_SEPARATOR . $path, $remove_parent_directory_listing);

        $presenter = new ExplorerPresenter(
            $path_parts,
            $path,
            $items,
            $project
        );

        $service->renderInPage(
            $request, 
            $project->getPublicName().' / '.$path,
            'index',
            $presenter
        );
    }

    private function userHasPermissionToExploreSFTP(PFUser $user, Project $project) {
        return $this->permissions_manager->userCanBrowseSFTP($user, $project);
    }

}