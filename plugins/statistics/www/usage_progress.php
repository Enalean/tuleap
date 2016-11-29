<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Statistics\AdminHeaderPresenter;

require_once 'pre.php';

$plugin_manager = PluginManager::instance();
$plugin         = $plugin_manager->getPluginByName('statistics');
if (! $plugin || ! $plugin_manager->isPluginAvailable($plugin)) {
    $GLOBALS['HTML']->redirect('/');
}

if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['HTML']->redirect('/');
}

$title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

$header_presenter = new AdminHeaderPresenter(
    $title,
    'usage_progress'
);

$admin_page_renderer = new AdminPageRenderer();
$admin_page_renderer->renderANoFramedPresenter(
    $title,
    ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
    'usage-progress',
    new \Tuleap\Statistics\UsageProgressPresenter($header_presenter)
);
