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
use ParagonIE\ConstantTime\Base32;
use Tuleap\Layout\BaseLayout;
use Tuleap\MFA\OTP\TOTP;
use Tuleap\MFA\OTP\TOTPModeBuilder;
use Tuleap\MFA\OTP\TOTPValidator;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class EnrollmentRegisterController implements DispatchableWithRequestNoAuthz
{
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $request_uri = $request->getFromServer('REQUEST_URI');

        $csrf_token = new \CSRFSynchronizerToken($request_uri);
        $csrf_token->check();

        $secret = $request->get('secret');
        $code   = $request->get('code');

        $totp           = new TOTP(TOTPModeBuilder::build(), Base32::decode($secret));
        $totp_validator = new TOTPValidator($totp);

        if ($totp_validator->validate($code, new \DateTimeImmutable())) {
            $layout->addFeedback(\Feedback::INFO, 'Valid code');
        } else {
            $layout->addFeedback(\Feedback::INFO, 'Not valid code');
        }


        $layout->redirect($request_uri);
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        return $request->getCurrentUser()->isLoggedIn();
    }
}
