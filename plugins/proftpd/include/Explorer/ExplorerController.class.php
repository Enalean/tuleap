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
    const NAME = 'explorer';

    /** @var Proftpd_Directory_DirectoryParser */
    private $parser;

    public function __construct(Proftpd_Directory_DirectoryParser $parser) {
        $this->parser  = $parser;
    }

    public function getName() {
        return self::NAME;
    }

    public function index(HTTPRequest $request) {
        $path_parser = new Proftpd_Directory_DirectoryPathParser();

        $path        = $path_parser->getCleanPath($request->get('path'));
        $path_parts  = $path_parser->getPathParts($path);

        $project        = $request->getProject();
        if (! $project ) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_proftpd', 'cannot_open_project'));
            return;
        }

        $remove_parent_directory_listing = ($path == '') ? true : false;
        $items = $this->parser->parseDirectory($project->getUnixName() . DIRECTORY_SEPARATOR . $path, $remove_parent_directory_listing);

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