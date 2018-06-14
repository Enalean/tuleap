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
use Exception;
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

        try {
            $this->saveEmail($request);
            $this->saveMessages($request);

            $layout->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-create_test_env', 'Call me back configuration updated')
            );
        } catch (PDOException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $layout->addFeedback(
                Feedback::ERROR,
                $exception->getMessage()
            );
        }

        $layout->redirect($request->getFromServer('REQUEST_URI'));
    }

    /**
     * @var string $email
     */
    private function saveEmail(HTTPRequest $request)
    {
        $email = $request->getValidated('email', 'email');

        if (! $email) {
            throw new Exception(
                dgettext('tuleap-create_test_env', 'An error occurred during the update of the email')
            );
        }

        $this->call_me_back_email_dao->save($email);
    }

    /**
     * @var string[] $messages
     */
    private function saveMessages(HTTPRequest $request)
    {
        $messages = $request->get('messages');

        if (! $messages) {
            throw new Exception(
                dgettext('tuleap-create_test_env', 'An error occurred during the update of the messages')
            );
        }

        foreach ($messages as $language_id => $message) {
            $this->call_me_back_message_dao->save($language_id, $message);
        }
    }
}
