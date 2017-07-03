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

namespace Tuleap\Git\Repository\Settings;

use Feedback;
use HTTPRequest;
use Tuleap\Git\Repository\RepositoryFromRequestRetriever;
use Tuleap\Git\Webhook\WebhookDao;

class WebhookAddController extends WebhookController
{
    /**
     * @var WebhookDao
     */
    private $dao;

    public function __construct(RepositoryFromRequestRetriever $repository_retriever, WebhookDao $dao)
    {
        parent::__construct($repository_retriever);
        $this->dao = $dao;
    }

    public function addWebhook(HTTPRequest $request)
    {
        $repository   = $this->getRepositoryUserCanAdministrate($request);
        $redirect_url = $this->getWebhookSettingsURL($repository);

        $this->checkCSRF($redirect_url);

        $webhook_url = $this->getURL($request, $redirect_url);

        if ($this->dao->create($repository->getId(), $webhook_url)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_add_success')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_git', 'settings_hooks_add_error')
            );
        }

        $GLOBALS['Response']->redirect($redirect_url);
    }
}
