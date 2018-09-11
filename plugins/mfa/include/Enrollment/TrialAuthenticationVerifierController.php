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
use Tuleap\MFA\Enrollment\TOTP\NotFoundTOTPEnrollmentException;
use Tuleap\MFA\Enrollment\TOTP\TOTPRetriever;
use Tuleap\MFA\OTP\TOTPValidator;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class TrialAuthenticationVerifierController implements DispatchableWithRequest
{
    /**
     * @var TOTPRetriever
     */
    private $totp_retriever;
    /**
     * @var TOTPValidator
     */
    private $totp_validator;

    public function __construct(TOTPRetriever $totp_retriever, TOTPValidator $totp_validator)
    {
        $this->totp_retriever = $totp_retriever;
        $this->totp_validator = $totp_validator;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user     = $request->getCurrentUser();
        $code_to_validate = $request->get('code');
        if ($code_to_validate === false || ! $current_user->isLoggedIn()) {
            throw new ForbiddenException();
        }

        $request_uri = $request->getFromServer('REQUEST_URI');
        $csrf_token  = new \CSRFSynchronizerToken($request_uri);
        $csrf_token->check();

        try {
            $totp = $this->totp_retriever->getTOTP($current_user);
        } catch (NotFoundTOTPEnrollmentException $exception) {
            throw new ForbiddenException();
        }

        $is_valid = $this->totp_validator->validate($totp, $code_to_validate, new \DateTimeImmutable());

        if ($is_valid) {
            $layout->addFeedback(\Feedback::INFO, 'Valid code');
        } else {
            $layout->addFeedback(\Feedback::ERROR, 'Not valid code');
        }

        $layout->redirect($request_uri);
    }
}
