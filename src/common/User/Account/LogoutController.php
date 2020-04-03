<?php
/**
 * Copyright (c) Enalean, 2016-2019. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class LogoutController implements DispatchableWithRequestNoAuthz
{
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(\UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $logout_csrf = new CSRFSynchronizerToken('logout_action');
        $logout_csrf->check('/my/');

        header('Clear-Site-Data: "cache", "storage", "executionContexts"');
        $this->user_manager->logout();

        $layout->redirect('/');
    }
}
