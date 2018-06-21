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
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class CallMeBackAdminSaveController implements DispatchableWithRequest
{
    /**
     * @var CallMeBackEmailDao
     */
    private $call_me_back_email_dao;
    /**
     * @var CallMeBackMessageDao
     */
    private $call_me_back_message_dao;

    public function __construct(
        CallMeBackEmailDao $call_me_back_email_dao,
        CallMeBackMessageDao $call_me_back_message_dao
    ) {
        $this->call_me_back_email_dao   = $call_me_back_email_dao;
        $this->call_me_back_message_dao = $call_me_back_message_dao;
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

        $csrf_token = new CSRFSynchronizerToken($request->getFromServer('REQUEST_URI'));
        $csrf_token->check();

        $this->saveAdminInformation($request, $layout);

        $layout->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-create_test_env', 'Call me back configuration updated')
        );

        $layout->redirect($request->getFromServer('REQUEST_URI'));
    }

    private function saveAdminInformation(HTTPRequest $request, BaseLayout $layout)
    {
        $email = $request->getValidated('email', 'email');
        if (! $email) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-create_test_env', 'An error occurred during the update of the email')
            );
            return;
        }
        $this->call_me_back_email_dao->save($email);

        $messages = $request->get('messages');
        if (! is_array($messages)) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-create_test_env', 'An error occurred during the update of the messages')
            );
            return;
        }
        $this->saveMessages($messages);
    }

    private function saveMessages(array $messages)
    {
        foreach ($messages as $language_id => $message) {
            $this->call_me_back_message_dao->save($language_id, $message);
        }
    }
}
