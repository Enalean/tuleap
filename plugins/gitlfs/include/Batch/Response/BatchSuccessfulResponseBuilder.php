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

use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationRequest;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationTokenCreator;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationType;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeUpload;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeVerify;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionHrefVerify;
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
    const EXPIRATION_DELAY_VERIFY_ACTION_IN_SEC = 6 * 3600;

    /**
     * @var ActionAuthorizationTokenCreator
     */
    private $authorization_token_creator;
    /**
     * @var SplitTokenFormatter
     */
    private $token_header_formatter;
    /**
     * @var \Logger
     */
    private $logger;

    public function __construct(
        ActionAuthorizationTokenCreator $authorization_token_creator,
        SplitTokenFormatter $token_header_formatter,
        \Logger $logger
    ) {
        $this->authorization_token_creator = $authorization_token_creator;
        $this->token_header_formatter      = $token_header_formatter;
        $this->logger                      = $logger;
    }

    public function build(
        \DateTimeImmutable $current_time,
        $server_url,
        \GitRepository $repository,
        BatchRequestOperation $operation,
        BatchRequestObject ...$request_objects
    ) {
        if (! $operation->isUpload()) {
            throw new UnknownOperationException('The requested operation is not known');
        }

        $response_objects = [];
        foreach ($request_objects as $request_object) {
            $upload_action_content = $this->buildActionContent(
                $current_time,
                $repository,
                $request_object,
                self::EXPIRATION_DELAY_UPLOAD_ACTION_IN_SEC,
                new ActionAuthorizationTypeUpload(),
                new BatchResponseActionHrefUpload($server_url, $request_object)
            );
            $verify_action_content = $this->buildActionContent(
                $current_time,
                $repository,
                $request_object,
                self::EXPIRATION_DELAY_VERIFY_ACTION_IN_SEC,
                new ActionAuthorizationTypeVerify(),
                new BatchResponseActionHrefVerify($server_url, $request_object)
            );
            $response_objects[]    = new BatchResponseObjectWithActions(
                $request_object->getOID(),
                $request_object->getSize(),
                new BatchResponseActionsForUploadOperation($upload_action_content, $verify_action_content)
            );
            $this->logger->debug('Ready to accept upload query for OID ' . $request_object->getOID());
        }

        return new BatchSuccessfulResponse(Transfer::buildBasicTransfer(), ...$response_objects);
    }

    private function buildActionContent(
        \DateTimeImmutable $current_time,
        \GitRepository $repository,
        BatchRequestObject $request_object,
        $expiration_delay,
        ActionAuthorizationType $action_type,
        BatchResponseActionHref $action_href
    ) {
        $authorization = new ActionAuthorizationRequest(
            $repository,
            $request_object,
            $action_type,
            $current_time->add(new \DateInterval('PT' . $expiration_delay . 'S'))
        );
        $authorization_token = $this->authorization_token_creator->createActionAuthorizationToken($authorization);

        return new BatchResponseActionContent(
            $action_href,
            $authorization_token,
            $this->token_header_formatter,
            $expiration_delay
        );
    }
}
