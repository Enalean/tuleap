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
 */

namespace Tuleap\Password\Administration;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Password\Configuration\PasswordConfigurationSaver;
use Tuleap\Request\DispatchableWithRequest;

class PasswordPolicyUpdateController implements DispatchableWithRequest
{
    /**
     * @var PasswordConfigurationSaver
     */
    private $password_configuration_saver;

    public function __construct(PasswordConfigurationSaver $password_configuration_saver)
    {
        $this->password_configuration_saver = $password_configuration_saver;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $request->checkUserIsSuperUser();

        $request_uri = $request->getFromServer('REQUEST_URI');
        $csrf_token  = new \CSRFSynchronizerToken($request_uri);
        $csrf_token->check();

        $this->password_configuration_saver->save($request->get('block-breached-password'));

        $layout->addFeedback(\Feedback::INFO, _('The password policy has been successfully updated.'));
        $layout->redirect($request_uri);
    }
}
