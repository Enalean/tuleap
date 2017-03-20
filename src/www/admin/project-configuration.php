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
use Tuleap\Project\Admin\WebhookPresenter;
use Tuleap\Project\Admin\WebhooksPresenter;
use Tuleap\Project\Webhook\Log\StatusRetriever;
use Tuleap\Project\Webhook\Log\WebhookLoggerDao;
use Tuleap\Project\Webhook\Retriever;
use Tuleap\Project\Webhook\WebhookDao;

require_once('pre.php');
require_once('www/admin/admin_utils.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

$title = $GLOBALS['Language']->getText('admin_sidebar', 'projects_nav_configuration');

$webhook_retriever        = new Retriever(new WebhookDao());
$webhooks                 = $webhook_retriever->getWebhooks();
$webhook_status_retriever = new StatusRetriever(new WebhookLoggerDao());
$webhooks_presenter       = array();

foreach ($webhooks as $webhook) {
    $webhooks_presenter[] = new WebhookPresenter(
        $webhook,
        $webhook_status_retriever->getMostRecentStatus($webhook)
    );
}

$presenter = new WebhooksPresenter($title, $webhooks_presenter);

$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/admin/project-configuration.js');

$admin_page = new AdminPageRenderer();
$admin_page->renderANoFramedPresenter(
    $title,
    ForgeConfig::get('codendi_dir') .'/src/templates/admin/projects/',
    'configuration',
    $presenter
);
