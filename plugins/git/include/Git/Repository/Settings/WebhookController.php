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

use CSRFSynchronizerToken;
use Feedback;
use GitRepository;
use HTTPRequest;
use Tuleap\Git\GitViews\RepoManagement\Pane;
use Valid_HTTPURI;

abstract class WebhookController extends SettingsController
{
    protected function getURL(HTTPRequest $request, $redirect_url)
    {
        $valid_url = new Valid_HTTPURI('url');
        $valid_url->required();
        if (! $request->valid($valid_url)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-git', 'Empty required parameter(s)')
            );
            $GLOBALS['Response']->redirect($redirect_url);
        }

        return $request->get('url');
    }

    protected function getWebhookSettingsURL(GitRepository $repository)
    {
        return GIT_BASE_URL . '/?' . http_build_query(array(
                'action'   => 'repo_management',
                'group_id' => $repository->getProjectId(),
                'repo_id'  => $repository->getId(),
                'pane'     => Pane\Hooks::ID
            ));
    }

    protected function checkCSRF($redirect_url)
    {
        $csrf = new CSRFSynchronizerToken(Pane\Hooks::CSRF_TOKEN_ID);
        $csrf->check($redirect_url);
    }
}
