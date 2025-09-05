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

namespace Tuleap\GitLFS\Transfer;

use HTTPRequest;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeVerify;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class LFSTransferVerifyController implements DispatchableWithRequestNoAuthz
{
    /**
     * @var LFSActionUserAccessHTTPRequestChecker
     */
    private $user_access_request_checker;
    /**
     * @var LFSTransferVerifier
     */
    private $transfer_verifier;

    public function __construct(
        LFSActionUserAccessHTTPRequestChecker $user_access_request_checker,
        LFSTransferVerifier $transfer_verifier,
    ) {
        $this->user_access_request_checker = $user_access_request_checker;
        $this->transfer_verifier           = $transfer_verifier;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');
        $authorized_action = $this->user_access_request_checker->userCanAccess(
            $request,
            new ActionAuthorizationTypeVerify(),
            $variables['oid']
        );

        try {
            $this->transfer_verifier->verifyAndMarkLFSObjectAsAvailable(
                $authorized_action->getLFSObject(),
                $authorized_action->getRepository()
            );
        } catch (LFSTransferVerificationException $exception) {
            http_response_code(400);
            echo $exception->getMessage();
        }
    }
}
