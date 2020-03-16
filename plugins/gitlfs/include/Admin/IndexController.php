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

namespace Tuleap\GitLFS\Admin;

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

class IndexController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var AdminDao
     */
    private $admin_dao;

    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    public function __construct(AdminDao $admin_dao, AdminPageRenderer $admin_page_renderer)
    {
        $this->admin_dao           = $admin_dao;
        $this->admin_page_renderer = $admin_page_renderer;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-gitlfs', 'You should be site administrator to access this page')
            );
            $layout->redirect('/');
            return;
        }

        $config_should_be_displayed = \ForgeConfig::get(\gitlfsPlugin::DISPLAY_CONFIG_KEY, true);
        if (! $config_should_be_displayed) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-gitlfs', 'The configuration page is not available.')
            );
            $layout->redirect('/');
            return;
        }

        $csrf_token            = new CSRFSynchronizerToken($request->getFromServer('REQUEST_URI'));
        $current_max_file_size = $this->admin_dao->getFileMaxSize();

        $this->admin_page_renderer->renderANoFramedPresenter(
            dgettext('tuleap-gitlfs', 'Git LFS'),
            __DIR__ . '/../../templates',
            'config',
            new IndexPresenter($csrf_token, $current_max_file_size)
        );
    }
}
