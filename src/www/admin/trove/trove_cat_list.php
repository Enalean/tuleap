<?php
/**
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

use Tuleap\Trove\TroveCatHierarchyRetriever;
use Tuleap\Trove\TroveCatListController;
use Tuleap\Trove\TroveCatRouter;
use Tuleap\Admin\AdminPageRenderer;

require_once('pre.php');

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/trovecat.js');

$trove_dao = new TroveCatDao();

$trove_cat_router = new TroveCatRouter(
    new TroveCatHierarchyRetriever($trove_dao),
    new AdminPageRenderer(),
    new TroveCatListController($trove_dao, new TroveCatFactory($trove_dao), new TroveCatHierarchyRetriever($trove_dao))
);
$trove_cat_router->route(HTTPRequest::instance());
