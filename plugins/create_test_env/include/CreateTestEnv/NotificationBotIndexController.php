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

namespace Tuleap\CreateTestEnv;

use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class NotificationBotIndexController implements DispatchableWithRequest
{
    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout $layout
     * @param array $variables
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            $layout->addFeedback(\Feedback::ERROR, dgettext('tuleap-create_test_env', 'You should be site administrator to access this page'));
            $layout->redirect('/');
            return;
        }

        $bots            = (new BotFactory(new BotDao()))->getBots();
        $selected_bot_id = (new NotificationBotDao())->get();

        $admin_page_renderer = new AdminPageRenderer();
        $admin_page_renderer->header(dgettext('tuleap-create_test_env', 'Create test environment'));
        $renderer =\TemplateRendererFactory::build()->getRenderer(__DIR__.'/../../templates');
        $renderer->renderToPage('admin', new NotificationBotPresenter($bots, $selected_bot_id));
        $admin_page_renderer->footer();
    }
}
