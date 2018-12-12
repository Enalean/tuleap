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
use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class IndexPostController implements DispatchableWithRequest
{
    /**
     * @var AdminDao
     */
    private $admin_dao;

    public function __construct(AdminDao $admin_dao)
    {
        $this->admin_dao = $admin_dao;
    }

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
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-gitlfs', 'You should be site administrator to access this page')
            );
            $layout->redirect('/');
            return;
        }

        $csrf_token = new CSRFSynchronizerToken($request->getFromServer('REQUEST_URI'));
        $csrf_token->check();

        $current_max_file_size = $this->admin_dao->getFileMaxSize();
        $new_max_file_value    = (int) $request->getValidated('max_file_size', 'uint', 1) * 1024 * 1024;

        if ($this->admin_dao->updateFileMaxSize($current_max_file_size, $new_max_file_value)) {
            $layout->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-gitlfs', 'Max file size updated.')
            );
        } else {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-gitlfs', 'An error occured while updating max file size.')
            );
        }

        $layout->redirect('/plugins/git-lfs/config');
    }
}
