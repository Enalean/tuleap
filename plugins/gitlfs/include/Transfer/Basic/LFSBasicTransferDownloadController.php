<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
use League\Flysystem\FilesystemReader;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeDownload;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\StreamFilter\StreamFilter;
use Tuleap\GitLFS\Transfer\BytesAmountHandledLFSObjectInstrumentationFilter;
use Tuleap\GitLFS\Transfer\LFSActionUserAccessHTTPRequestChecker;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class LFSBasicTransferDownloadController implements DispatchableWithRequestNoAuthz
{
    /**
     * @var LFSActionUserAccessHTTPRequestChecker
     */
    private $user_access_request_checker;
    /**
     * @var FilesystemReader
     */
    private $filesystem;
    /**
     * @var LFSObjectPathAllocator
     */
    private $path_allocator;
    /**
     * @var Prometheus
     */
    private $prometheus;

    public function __construct(
        LFSActionUserAccessHTTPRequestChecker $user_access_request_checker,
        FilesystemReader $filesystem,
        LFSObjectPathAllocator $path_allocator,
        Prometheus $prometheus,
    ) {
        $this->user_access_request_checker = $user_access_request_checker;
        $this->filesystem                  = $filesystem;
        $this->path_allocator              = $path_allocator;
        $this->prometheus                  = $prometheus;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
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
        header('X-DNS-Prefetch-Control: off');

        $object_resource = $this->filesystem->readStream($object_path);

        $transmitted_bytes_instrumentation_filter = BytesAmountHandledLFSObjectInstrumentationFilter::buildTransmittedBytesFilter(
            $this->prometheus,
            'basic'
        );
        $output_resource                          = fopen('php://output', 'ab');
        $received_bytes_filter_handle             = StreamFilter::prependFilter($object_resource, $transmitted_bytes_instrumentation_filter);

        stream_copy_to_stream($object_resource, $output_resource);

        StreamFilter::removeFilter($received_bytes_filter_handle);
    }
}
