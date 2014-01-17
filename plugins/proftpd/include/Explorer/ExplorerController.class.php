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
        $parser      = new Proftpd_Directory_DirectoryParser();
        $path_parser = new Proftpd_Directory_DirectoryPathParser();

        $path        = $path_parser->getCleanPath($this->request->get('path'));
        $path_parts  = $path_parser->getPathParts($path);

        $base_directory = $this->request->get('proftpd_base_directory');
        $project        = $this->request->getProject();

        if (! $project || ! $base_directory) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_proftpd', 'cannot_open_project'));
            return;
        }

        $remove_parent_directory_listing = ($path == '') ? true : false;
        $items = $parser->parseDirectory($base_directory.'/'.$path, $remove_parent_directory_listing);

        $presenter = new Proftpd_Presenter_ExplorerPresenter(
            $path_parts,
            $path,
            $items,
            $project
        );

        echo $this->getRenderer()->renderToString('index', $presenter);
    }

    private function getRenderer() {
        return TemplateRendererFactory::build()->getRenderer(dirname(PROFTPD_BASE_DIR).'/templates');
    }
}
?>