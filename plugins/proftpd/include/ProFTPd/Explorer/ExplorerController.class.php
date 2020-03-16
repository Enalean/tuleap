<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All rights reserved
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

use GuzzleHttp\Psr7\ServerRequest;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\ProFTPd\Directory\DirectoryParser;
use Tuleap\ProFTPd\Directory\DirectoryPathParser;
use Tuleap\ProFTPd\Presenter\ExplorerPresenter;
use Tuleap\ProFTPd\ServiceProFTPd;
use Tuleap\ProFTPd\Admin\PermissionsManager;
use Tuleap\ProFTPd\Xferlog\Dao;
use HTTPRequest;
use PFUser;
use Project;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;

class ExplorerController
{
    public const NAME = 'explorer';
    public const TRANSFERT_BUFFER_SIZE = 8192;

    /** @var DirectoryParser */
    private $parser;

    /** @var PermissionsManager */
    private $permissions_manager;

    /** @var Dao */
    private $xferlog_dao;

    public function __construct(DirectoryParser $parser, PermissionsManager $permissions_manager, Dao $xferlog_dao)
    {
        $this->parser              = $parser;
        $this->permissions_manager = $permissions_manager;
        $this->xferlog_dao         = $xferlog_dao;
    }

    public function getName()
    {
        return self::NAME;
    }

    public function index(ServiceProFTPd $service, HTTPRequest $request)
    {
        if ($this->userHasPermissionToExploreSFTP($request->getCurrentUser(), $request->getProject())) {
            $this->renderIndex($service, $request);
        } else {
            $GLOBALS['Response']->addFeedback(
                'error',
                dgettext('tuleap-proftpd', "You're not granted sufficient rights to browse the (S)FTP repository")
            );
            $service->renderInPage(
                $request,
                '',
                'index',
                null
            );
        }
    }

    private function renderIndex(ServiceProFTPd $service, HTTPRequest $request)
    {
        $project = $request->getProject();
        if (! $project) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-proftpd', 'Cannot open project'));
            return;
        }

        $path_parser = new DirectoryPathParser();
        $path = $path_parser->getCleanPath($request->get('path'));
        if ($this->parser->isFile($project->getUnixName() . DIRECTORY_SEPARATOR . $path)) {
            $this->renderFileContent($request, $project, $project->getUnixName() . DIRECTORY_SEPARATOR . $path);
        } else {
            $this->renderDirectoryContent($service, $request, $path_parser, $project, $path);
        }
    }

    private function renderFileContent(HTTPRequest $request, Project $project, $project_path)
    {
        $full_path = $this->parser->getFullPath($project_path);
        $this->xferlog_dao->storeWebDownload(
            $request->getCurrentUser()->getId(),
            $project->getID(),
            $_SERVER['REQUEST_TIME'],
            $project_path
        );

        $response_builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());
        $response         = $response_builder->fromFilePath(ServerRequest::fromGlobals(), $full_path, basename($full_path));
        (new SapiStreamEmitter())->emit($response);
        exit();
    }

    private function renderDirectoryContent(ServiceProFTPd $service, HTTPRequest $request, DirectoryPathParser $path_parser, Project $project, $path)
    {
        $remove_parent_directory_listing = ($path == '') ? true : false;
        $items = $this->parser->parseDirectory($project->getUnixName() . DIRECTORY_SEPARATOR . $path, $remove_parent_directory_listing);

        $service->renderInPage(
            $request,
            $project->getPublicName() . ' / ' . $path,
            'index',
            new ExplorerPresenter(
                $path_parser->getPathParts($path),
                $path,
                $items,
                $project
            )
        );
    }

    private function userHasPermissionToExploreSFTP(PFUser $user, Project $project)
    {
        return $this->permissions_manager->userCanBrowseSFTP($user, $project);
    }
}
