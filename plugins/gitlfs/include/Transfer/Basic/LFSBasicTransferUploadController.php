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

namespace Tuleap\GitLFS\Transfer\Basic;

use HTTPRequest;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeUpload;
use Tuleap\GitLFS\Transfer\AuthorizedActionStore;
use Tuleap\GitLFS\Transfer\LFSActionUserAccessHTTPRequestChecker;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class LFSBasicTransferUploadController implements DispatchableWithRequestNoAuthz
{
    /**
     * @var LFSActionUserAccessHTTPRequestChecker
     */
    private $user_access_request_checker;
    /**
     * @var AuthorizedActionStore
     */
    private $authorized_action_store;
    /**
     * @var LFSBasicTransferObjectSaver
     */
    private $basic_object_saver;

    public function __construct(
        LFSActionUserAccessHTTPRequestChecker $user_access_request_checker,
        AuthorizedActionStore $authorized_action_store,
        LFSBasicTransferObjectSaver $basic_object_saver
    ) {
        $this->user_access_request_checker = $user_access_request_checker;
        $this->authorized_action_store     = $authorized_action_store;
        $this->basic_object_saver          = $basic_object_saver;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $authorized_action = $this->authorized_action_store->getAuthorizedAction();

        $input_resource = fopen('php://input', 'rb');
        try {
            $this->basic_object_saver->saveObject(
                $authorized_action->getRepository(),
                $authorized_action->getLFSObject(),
                $input_resource
            );
        } catch (LFSBasicTransferException $exception) {
            http_response_code(400);
            echo $exception->getMessage();
        } finally {
            fclose($input_resource);
        }
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');
        return $this->user_access_request_checker->userCanAccess(
            $this->authorized_action_store,
            $request,
            new ActionAuthorizationTypeUpload(),
            $variables['oid']
        );
    }
}
