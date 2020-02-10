<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Webhook\WebhookDao;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class WebhooksUpdateController implements DispatchableWithRequest
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

        $csrf_token->check();

        $webhook_updater = new \Tuleap\Project\Webhook\WebhookUpdater($webhook_dao);

        switch ($request->get('action')) {
            case 'add':
                try {
                    $webhook_updater->add($request->get('name'), $request->get('url'));
                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_add_success')
                    );
                } catch (\Tuleap\Project\Webhook\WebhookDataAccessException $ex) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_add_error')
                    );
                } catch (\Tuleap\Project\Webhook\WebhookMalformedDataException $ex) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_add_error')
                    );
                }
                break;
            case 'update':
                try {
                    $webhook_updater->edit($request->get('id'), $request->get('name'), $request->get('url'));
                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_edit_success')
                    );
                } catch (\Tuleap\Project\Webhook\WebhookDataAccessException $ex) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_edit_error')
                    );
                } catch (\Tuleap\Project\Webhook\WebhookMalformedDataException $ex) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_edit_error')
                    );
                }
                break;
            case 'delete':
                try {
                    $webhook_updater->delete($request->get('id'));
                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_delete_success')
                    );
                } catch (\Tuleap\Project\Webhook\WebhookDataAccessException $ex) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('admin_project_configuration', 'webhook_delete_error')
                    );
                }
                break;
        }

        $layout->redirect('/admin/project-creation/webhooks');
    }
}
