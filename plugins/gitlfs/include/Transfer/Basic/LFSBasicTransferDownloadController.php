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
use League\Flysystem\FilesystemInterface;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeDownload;
use Tuleap\GitLFS\Object\LFSObjectPathAllocator;
use Tuleap\GitLFS\Object\LFSObjectRetriever;
use Tuleap\GitLFS\Transfer\AuthorizedActionStore;
use Tuleap\GitLFS\Transfer\LFSActionUserAccessHTTPRequestChecker;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class LFSBasicTransferDownloadController implements DispatchableWithRequestNoAuthz
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
     * @var FilesystemInterface
     */
    private $filesystem;
    /**
     * @var LFSObjectPathAllocator
     */
    private $path_allocator;

    public function __construct(
        LFSActionUserAccessHTTPRequestChecker $user_access_request_checker,
        AuthorizedActionStore $authorized_action_store,
        FilesystemInterface $filesystem,
        LFSObjectPathAllocator $path_allocator
    ) {
        $this->user_access_request_checker = $user_access_request_checker;
        $this->authorized_action_store     = $authorized_action_store;
        $this->filesystem                  = $filesystem;
        $this->path_allocator              = $path_allocator;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $authorized_action = $this->authorized_action_store->getAuthorizedAction();

        $lfs_object  = $authorized_action->getLFSObject();
        $object_path = $this->path_allocator->getPathForAvailableObject($lfs_object);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment');
        header('Content-Length: ' . $lfs_object->getSize());
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; form-action 'none';");
        header('X-DNS-Prefetch-Control: off');

        fpassthru($this->filesystem->readStream($object_path));
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');
        return $this->user_access_request_checker->userCanAccess(
            $this->authorized_action_store,
            $request,
            new ActionAuthorizationTypeDownload(),
            $variables['oid']
        );
    }
}
