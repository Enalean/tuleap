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

class Proftpd_ExplorerController {

    /**
     * @var HTTPRequest
     */
    private $request;

    public function __construct(HTTPRequest $request) {
        $this->request = $request;
    }

    public function index() {
        $path       = $this->getDirectoryPath();
        $path_parts = $this->getPathParts($path);

        $parser     = new Proftpd_Directory_DirectoryParser();
        $items      = $parser->parseDirectory(proftpdPlugin::BASE_DIRECTORY.'/'.$path);
        $project    = $this->request->getProject();

        if (! $project) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_proftpd', 'cannot_open_project'));
            return;
        }

        $presenter = new Proftpd_Presenter_ExplorerPresenter(
            $path_parts,
            $path,
            $items,
            $project
        );

        echo $this->getRenderer()->renderToString('index', $presenter);
    }

    private function getDirectoryPath() {
        $path = $this->request->get('path');
        if (! $path) {
            return '';
        }

        return urldecode($path);
    }

    private function getPathParts($path) {
        $path_parser = new Proftpd_Directory_DirectoryPathParser();
        return $path_parser->getPathParts($path);
    }

    private function userCanAccess(Project $project) {
        $user = $this->request->getCurrentUser();

        if (! $project->isPublic() && ! $user->isMember($project->getID()) && ! $user->isSuperUser()) {
            return false;
        }
        return true;
    }

    private function getRenderer() {
        return TemplateRendererFactory::build()->getRenderer(dirname(PROFTPD_BASE_DIR).'/templates');
    }
}
?>