<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Admin\ProjectCreation;

use CSRFSynchronizerToken;
use ForgeConfig;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\ProjectCreationNavBarPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Project\Admin\WebhookPresenter;
use Tuleap\Project\Admin\WebhooksPresenter;
use Tuleap\Project\Webhook\Log\StatusRetriever;
use Tuleap\Project\Webhook\Log\WebhookLoggerDao;
use Tuleap\Project\Webhook\Retriever;
use Tuleap\Project\Webhook\WebhookDao;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class WebhooksDisplayController implements DispatchableWithRequest
{
    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $csrf_token = new CSRFSynchronizerToken('/project-creation/webhooks');

        $webhook_dao = new WebhookDao();

        $webhook_retriever        = new Retriever($webhook_dao);
        $webhooks                 = $webhook_retriever->getWebhooks();
        $webhook_status_retriever = new StatusRetriever(new WebhookLoggerDao());
        $webhooks_presenter       = [];

        foreach ($webhooks as $webhook) {
            $webhooks_presenter[] = new WebhookPresenter(
                $webhook,
                $webhook_status_retriever->getMostRecentStatus($webhook)
            );
        }

        $title     = _('Project settings');
        $presenter = new WebhooksPresenter(
            new ProjectCreationNavBarPresenter('webhooks'),
            $title,
            $webhooks_presenter,
            $csrf_token
        );

        $include_assets = new IncludeAssets(__DIR__ . '/../../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
        $layout->addJavascriptAsset(new JavascriptAsset($include_assets, 'site-admin-project-configuration.js'));

        $admin_page = new AdminPageRenderer();
        $admin_page->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/',
            'configuration',
            $presenter
        );
    }
}
