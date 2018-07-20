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
use Tuleap\Layout\BaseLayout;
use Tuleap\MFA\Enrollment\TOTP\EnrollmentTOTPException;
use Tuleap\MFA\Enrollment\TOTP\TOTPEnroller;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class EnrollmentRegisterController implements DispatchableWithRequestNoAuthz
{
    /**
     * @var TOTPEnroller
     */
    private $totp_enroller;

    public function __construct(TOTPEnroller $totp_enroller)
    {
        $this->totp_enroller = $totp_enroller;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $request_uri = $request->getFromServer('REQUEST_URI');

        $csrf_token = new \CSRFSynchronizerToken($request_uri);
        $csrf_token->check();

        $code = $request->get('code');

        try {
            $this->totp_enroller->enrollUser($request->getCurrentUser(), $_SESSION, $code);
            $layout->addFeedback(\Feedback::INFO, 'Valid code, user enrolled');
        } catch (EnrollmentTOTPException $ex) {
            $layout->addFeedback(\Feedback::INFO, 'Not valid code or incorrect state, enrollment failed');
        }

        $layout->redirect($request_uri);
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        return $request->getCurrentUser()->isLoggedIn();
    }
}
