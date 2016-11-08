<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

use Tuleap\SvnCore\Admin\CacheController;
use Tuleap\SvnCore\Admin\Renderer;
use Tuleap\SvnCore\Admin\Router;
use Tuleap\SvnCore\Admin\TokenController;
use Tuleap\SvnCore\Cache\ParameterDao;
use Tuleap\SvnCore\Cache\ParameterRetriever;
use Tuleap\SvnCore\Cache\ParameterSaver;

require_once 'pre.php';

$project_manager     = ProjectManager::instance();
$token_manager       = new SVN_TokenUsageManager(new SVN_TokenDao(), $project_manager);
$event_manager       = EventManager::instance();
$parameter_dao       = new ParameterDao();
$parameter_retriever = new ParameterRetriever($parameter_dao);
$parameters          = $parameter_retriever->getParameters();
$parameter_saver     = new ParameterSaver($parameter_dao, $event_manager);
$csrf_token          = new CSRFSynchronizerToken('/admin/svn/index.php?pane=index');
$renderer            = new Renderer();

$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/manage-allowed-projects-on-resource.js');

$token_controller = new TokenController($project_manager, $token_manager, $event_manager, $renderer, $csrf_token);
$cache_controller = new CacheController($parameters, $parameter_saver, $renderer, $csrf_token);

$router  = new Router($cache_controller, $token_controller);
$request = HTTPRequest::instance();
$router->process($request);
