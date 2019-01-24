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
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
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
     * @var FilesystemInterface
     */
    private $filesystem;
    /**
     * @var LFSObjectPathAllocator
     */
    private $path_allocator;

    public function __construct(
        LFSActionUserAccessHTTPRequestChecker $user_access_request_checker,
        FilesystemInterface $filesystem,
        LFSObjectPathAllocator $path_allocator
    ) {
        $this->user_access_request_checker = $user_access_request_checker;
        $this->filesystem                  = $filesystem;
        $this->path_allocator              = $path_allocator;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');
        $authorized_action = $this->user_access_request_checker->userCanAccess(
            $request,
            new ActionAuthorizationTypeDownload(),
            $variables['oid']
        );

        $lfs_object  = $authorized_action->getLFSObject();
        $object_path = $this->path_allocator->getPathForAvailableObject($lfs_object);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment');
        header('Content-Length: ' . $lfs_object->getSize());
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; form-action 'none';");
        header('X-DNS-Prefetch-Control: off');

        ob_end_flush();

        fpassthru($this->filesystem->readStream($object_path));
    }
}
