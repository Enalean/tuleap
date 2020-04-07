<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\Authentication;

use Feedback;
use Tuleap\OpenIDConnectClient\Login\Controller;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class AzureADUserLinkController implements DispatchableWithRequest, DispatchableWithRequestNoAuthz
{
    /**
     * @var Controller
     */
    private $login_controller;

    public function __construct(Controller $login_controller)
    {
        $this->login_controller = $login_controller;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $this->checkTLSPresence($request, $layout);

        $this->login_controller->login($request, $request->get('return_to'), $request->getTime());
    }

    private function checkTLSPresence(HTTPRequest $request, BaseLayout $layout): void
    {
        if (! $request->isSecure()) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-openidconnectclient', 'The OpenID Connect plugin can only be used if the platform is accessible with HTTPS')
            );
            $layout->redirect('/account/login.php');
        }
    }
}
