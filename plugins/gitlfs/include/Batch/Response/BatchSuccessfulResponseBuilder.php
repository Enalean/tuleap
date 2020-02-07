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
use Tuleap\GitLFS\Admin\AdminDao;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationRequest;
use Tuleap\GitLFS\Authorization\Action\ActionAuthorizationTokenCreator;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationType;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeDownload;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeUpload;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeVerify;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionContent;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionHrefDownload;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionHrefVerify;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionsForDownloadOperation;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionsForUploadOperation;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\GitLFS\Transfer\Transfer;
use Tuleap\GitLFS\Batch\Request\BatchRequestOperation;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionHref;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionHrefUpload;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Project\Quota\ProjectQuotaChecker;

class BatchSuccessfulResponseBuilder
{
    public const EXPIRATION_DELAY_UPLOAD_ACTION_IN_SEC = 900;
    public const EXPIRATION_DELAY_VERIFY_ACTION_IN_SEC = 6 * 3600;
    public const EXPIRATION_DELAY_DOWNLOAD_ACTION_IN_SEC = 3600;

    /**
     * @var ActionAuthorizationTokenCreator
     */
    private $authorization_token_creator;
    /**
     * @var SplitTokenFormatter
     */
    private $token_header_formatter;
    /**
     * @var LFSObjectRetriever
     */
    private $lfs_object_retriever;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var AdminDao
     */
    private $admin_dao;

    /**
     * @var ProjectQuotaChecker
     */
    private $project_quota_checker;
    /**
     * @var Prometheus
     */
    private $prometheus;

    public function __construct(
        ActionAuthorizationTokenCreator $authorization_token_creator,
        SplitTokenFormatter $token_header_formatter,
        LFSObjectRetriever $lfs_object_retriever,
        AdminDao $admin_dao,
        ProjectQuotaChecker $project_quota_checker,
        \Psr\Log\LoggerInterface $logger,
        Prometheus $prometheus
    ) {
        $this->authorization_token_creator = $authorization_token_creator;
        $this->token_header_formatter      = $token_header_formatter;
        $this->lfs_object_retriever        = $lfs_object_retriever;
        $this->logger                      = $logger;
        $this->admin_dao                   = $admin_dao;
        $this->project_quota_checker       = $project_quota_checker;
        $this->prometheus                  = $prometheus;
    }

    public function build(
        \DateTimeImmutable $current_time,
        $server_url,
        \GitRepository $repository,
        BatchRequestOperation $operation,
        LFSObject ...$request_objects
    ) {
        $response_objects = null;
        if ($operation->isUpload()) {
            $response_objects = $this->buildUploadResponseObjects(
                $current_time,
                $server_url,
                $repository,
                ...$request_objects
            );
        }
        if ($operation->isDownload()) {
            $response_objects = $this->buildDownloadResponseObjects(
                $current_time,
                $server_url,
                $repository,
                ...$request_objects
            );
        }

        if ($response_objects !== null) {
            return new BatchSuccessfulResponse(Transfer::buildBasicTransfer(), ...$response_objects);
        }

        throw new UnknownOperationException('The requested operation is not known');
    }

    /**
     * @return BatchResponseObject[]
     */
    private function buildUploadResponseObjects(
        \DateTimeImmutable $current_time,
        $server_url,
        \GitRepository $repository,
        LFSObject ...$request_objects
    ) {
        $existing_objects = $this->lfs_object_retriever->getExistingLFSObjectsFromTheSetForRepository(
            $repository,
            ...$request_objects
        );

        $this->checkProjectQuota($repository->getProject(), ...$request_objects);

        $max_file_size = $this->admin_dao->getFileMaxSize();

        $response_objects = [];
        foreach ($request_objects as $request_object) {
            if (!in_array($request_object, $existing_objects, true)) {
                $this->checkFileMaxSize($request_object, $max_file_size);

                $upload_action_content = $this->buildSuccessActionContent(
                    $current_time,
                    $repository,
                    $request_object,
                    self::EXPIRATION_DELAY_UPLOAD_ACTION_IN_SEC,
                    new ActionAuthorizationTypeUpload(),
                    new BatchResponseActionHrefUpload($server_url, $request_object)
                );
                $verify_action_content = $this->buildSuccessActionContent(
                    $current_time,
                    $repository,
                    $request_object,
                    self::EXPIRATION_DELAY_VERIFY_ACTION_IN_SEC,
                    new ActionAuthorizationTypeVerify(),
                    new BatchResponseActionHrefVerify($server_url, $request_object)
                );
                $response_objects[] = new BatchResponseObjectWithActions(
                    $request_object,
                    new BatchResponseActionsForUploadOperation($upload_action_content, $verify_action_content)
                );
                $this->prometheus->increment(
                    'gitlfs_accepted_upload_requests_total',
                    'Total number of accepted Git LFS upload requests',
                    ['transfer' => 'basic']
                );
                $this->logger->debug('Ready to accept upload query for OID ' . $request_object->getOID()->getValue());
            } else {
                $response_objects[] = new BatchResponseObjectWithoutAction($request_object);
            }
        }
        return $response_objects;
    }

    private function checkProjectQuota(\Project $project, LFSObject ...$request_objects)
    {
        $wanted_size = 0;

        foreach ($request_objects as $request_object) {
            $wanted_size += $request_object->getSize();
        }

        if (! $this->project_quota_checker->hasEnoughSpaceForProject($project, $wanted_size)) {
            throw new ProjectQuotaExceededException(
                "Quota for project " . $project->getID() . " is exceeded",
                507
            );
        }
    }

    /**
     * @throws MaxFileSizeException
     */
    private function checkFileMaxSize(LFSObject $request_object, $max_file_size)
    {
        if ($request_object->getSize() > $max_file_size) {
            $max_file_size_in_mega_bytes = round(($max_file_size / 1024) / 1024);
            throw new MaxFileSizeException(
                "The file size is over $max_file_size_in_mega_bytes Mb. Aborting",
                429
            );
        }
    }

    private function buildDownloadResponseObjects(
        \DateTimeImmutable $current_time,
        $server_url,
        \GitRepository $repository,
        LFSObject ...$request_objects
    ) {
        $existing_objects = $this->lfs_object_retriever->getExistingLFSObjectsFromTheSetForRepository(
            $repository,
            ...$request_objects
        );
        $response_objects = [];
        foreach ($request_objects as $request_object) {
            if (in_array($request_object, $existing_objects, true)) {
                $download_action_content = $this->buildSuccessActionContent(
                    $current_time,
                    $repository,
                    $request_object,
                    self::EXPIRATION_DELAY_DOWNLOAD_ACTION_IN_SEC,
                    new ActionAuthorizationTypeDownload(),
                    new BatchResponseActionHrefDownload($server_url, $request_object)
                );
                $response_objects[]    = new BatchResponseObjectWithActions(
                    $request_object,
                    new BatchResponseActionsForDownloadOperation($download_action_content)
                );
                $this->prometheus->increment(
                    'gitlfs_accepted_download_requests_total',
                    'Total number of accepted Git LFS download requests',
                    ['transfer' => 'basic']
                );
                $this->logger->debug('Ready to accept download query for OID ' . $request_object->getOID()->getValue());
            } else {
                $response_objects[] = new BatchResponseObjectWithNotFoundError($request_object);
            }
        }
        return $response_objects;
    }

    private function buildSuccessActionContent(
        \DateTimeImmutable $current_time,
        \GitRepository $repository,
        LFSObject $request_object,
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
