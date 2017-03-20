<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Project\Admin\WebhooksPresenter;

require_once('pre.php');
require_once('www/admin/admin_utils.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

$title = $GLOBALS['Language']->getText('admin_sidebar', 'projects_nav_configuration');

$webhook_retriever = new \Tuleap\Project\Webhook\Retriever(new \Tuleap\Project\Webhook\WebhookDao());
$webhooks          = $webhook_retriever->getWebhooks();

$presenter = new WebhooksPresenter($title, $webhooks);

$admin_page = new AdminPageRenderer();
$admin_page->renderANoFramedPresenter(
    $title,
    ForgeConfig::get('codendi_dir') .'/src/templates/admin/projects/',
    'configuration',
    $presenter
);
