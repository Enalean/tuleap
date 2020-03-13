<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use Tuleap\News\Admin\AdminNewsBuilder;
use Tuleap\News\Admin\AdminNewsController;
use Tuleap\News\Admin\AdminNewsRouter;
use Tuleap\News\Admin\AdminNewsDao;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\News\Admin\NewsRetriever;

require_once __DIR__ . '/../../include/pre.php';

//common forum tools which are used during the creation/editing of news items
require_once __DIR__ . '/../../forum/forum_utils.php';
require_once __DIR__ . '/../../project/admin/ugroup_utils.php';


$request = HTTPRequest::instance();

if (user_ismember($GLOBALS['sys_news_group'], 'A')) {
    $admin_news_renderer = new AdminPageRenderer();
    $csrf_token          = new CSRFSynchronizerToken('/admin/news');
    $admin_news_dao      = new AdminNewsDao();
    $admin_news_builder  = new AdminNewsBuilder(
        $csrf_token,
        new NewsRetriever($admin_news_dao),
        ProjectManager::instance(),
        UserManager::instance()
    );
    $admin_news_router  = new AdminNewsRouter(
        new AdminNewsController(
            $admin_news_dao,
            $admin_news_renderer,
            $admin_news_builder
        )
    );
    $admin_news_router->route($request);
} else {
    exit_error(
        $Language->getText('news_admin_index', 'permission_denied'),
        $Language->getText('news_admin_index', 'need_to_be_admin')
    );
}
