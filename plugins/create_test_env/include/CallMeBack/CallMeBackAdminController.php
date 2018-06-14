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

namespace Tuleap\CallMeBack;

use HTTPRequest;
use Feedback;
use CSRFSynchronizerToken;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class CallMeBackAdminController implements DispatchableWithRequest
{
    /**
     * @var CallMeBackEmailDao
     */
    private $call_me_back_email_dao;
    /**
     * @var CallMeBackMessageDao
     */
    private $call_me_back_message_dao;
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    public function __construct(
        CallMeBackEmailDao $call_me_back_email_dao,
        CallMeBackMessageDao $call_me_back_message_dao,
        AdminPageRenderer $admin_page_renderer
    ) {
        $this->call_me_back_email_dao   = $call_me_back_email_dao;
        $this->call_me_back_message_dao = $call_me_back_message_dao;
        $this->admin_page_renderer      = $admin_page_renderer;
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
                dgettext('tuleap-create_test_env', 'You should be site administrator to access this page')
            );
            $layout->redirect('/');
            return;
        }

        $email = $this->call_me_back_email_dao->get();

        $call_me_back_message_rows = $this->call_me_back_message_dao->getAll();
        $call_me_back_messages     = array();
        foreach ($call_me_back_message_rows as $row) {
            $call_me_back_messages[] = new CallMeBackMessage($row['language_id'], $row['message']);
        }

        $csrf_token = new CSRFSynchronizerToken($request->getFromServer('REQUEST_URI'));

        $this->admin_page_renderer->renderANoFramedPresenter(
            dgettext('tuleap-create_test_env', 'Create test environment'),
            __DIR__.'/../../templates',
            'call-me-back-tab',
            new CallMeBackAdminPresenter(
                $email,
                $call_me_back_messages,
                $csrf_token
            )
        );
    }
}
