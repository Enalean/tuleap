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

namespace Tuleap\GitLFS\Transfer\Basic;

use HTTPRequest;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeUpload;
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
     * @var LFSBasicTransferObjectSaver
     */
    private $basic_object_saver;

    public function __construct(
        LFSActionUserAccessHTTPRequestChecker $user_access_request_checker,
        LFSBasicTransferObjectSaver $basic_object_saver,
    ) {
        $this->user_access_request_checker = $user_access_request_checker;
        $this->basic_object_saver          = $basic_object_saver;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');
        $authorized_action = $this->user_access_request_checker->userCanAccess(
            $request,
            new ActionAuthorizationTypeUpload(),
            $variables['oid']
        );

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
}
