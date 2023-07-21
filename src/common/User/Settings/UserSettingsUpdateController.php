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

namespace Tuleap\User\Settings;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

final class UserSettingsUpdateController implements DispatchableWithRequest
{
    public function __construct(private readonly \Tuleap\Config\ConfigDao $config_dao)
    {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $request->checkUserIsSuperUser();

        $request_uri = $request->getFromServer(('REQUEST_URI'));
        $csrf_token  = new \CSRFSynchronizerToken("/admin/user-settings/");
        $csrf_token->check();


        $user_approval = $request->getToggleVariable('users_must_be_approved');
        $this->config_dao->save(\UserManager::CONFIG_USER_APPROVAL, $user_approval);

        $layout->addFeedback(\Feedback::INFO, _('User approval settings have been saved'));
        $layout->redirect($request_uri);
    }
}
