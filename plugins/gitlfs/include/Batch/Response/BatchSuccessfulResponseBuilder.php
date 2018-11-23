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

namespace Tuleap\GitLFS\Batch\Response;

use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionsForUploadOperation;
use Tuleap\GitLFS\Transfer\Transfer;
use Tuleap\GitLFS\Batch\Request\BatchRequestObject;
use Tuleap\GitLFS\Batch\Request\BatchRequestOperation;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionContent;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionHref;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionHrefUpload;

class BatchSuccessfulResponseBuilder
{
    const EXPIRATION_DELAY_UPLOAD_ACTION_IN_SEC = 900;

    /**
     * @var \Logger
     */
    private $logger;

    public function __construct(
        \Logger $logger
    ) {
        $this->logger = $logger;
    }

    public function build(
        $server_url,
        BatchRequestOperation $operation,
        BatchRequestObject ...$request_objects
    ) {
        if (! $operation->isUpload()) {
            throw new UnknownOperationException('The requested operation is not known');
        }

        $response_objects = [];
        foreach ($request_objects as $request_object) {
            $upload_action_content = $this->buildActionContent(
                new BatchResponseActionHrefUpload($server_url, $request_object)
            );
            $response_objects[]    = new BatchResponseObjectWithActions(
                $request_object->getOID(),
                $request_object->getSize(),
                new BatchResponseActionsForUploadOperation($upload_action_content)
            );
            $this->logger->debug('Ready to accept upload query for OID ' . $request_object->getOID());
        }

        return new BatchSuccessfulResponse(Transfer::buildBasicTransfer(), ...$response_objects);
    }

    private function buildActionContent(BatchResponseActionHref $action_href)
    {
        return new BatchResponseActionContent(
            $action_href,
            self::EXPIRATION_DELAY_UPLOAD_ACTION_IN_SEC
        );
    }
}
