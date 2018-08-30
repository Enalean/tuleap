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
 */

namespace Tuleap\MFA\Enrollment;

use HTTPRequest;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\MFA\Enrollment\TOTP\TOTPEnroller;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class EnrollmentDisplayController implements DispatchableWithRequestNoAuthz
{
    /**
     * @var \TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var TOTPEnroller
     */
    private $totp_enroller;

    public function __construct(\TemplateRenderer $template_renderer, TOTPEnroller $totp_enroller)
    {
        $this->template_renderer = $template_renderer;
        $this->totp_enroller     = $totp_enroller;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $csrf_token = new \CSRFSynchronizerToken($request->getFromServer('REQUEST_URI'));

        $is_user_already_registered = $this->totp_enroller->isUserEnrolled($request->getCurrentUser());
        $secret                     = $this->totp_enroller->prepareSessionForEnrollment($_SESSION);

        $layout->header(['title' => dgettext('tuleap-mfa', 'Enable two-factor authentication')]);
        $this->template_renderer->renderToPage(
            'enrollment',
            new EnrollmentPresenter($csrf_token, $secret, $is_user_already_registered)
        );
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        return $request->getCurrentUser()->isLoggedIn();
    }
}
