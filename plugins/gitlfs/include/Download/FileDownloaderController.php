<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\GitLFS\Download;

use GitRepositoryFactory;
use HTTPRequest;
use League\Flysystem\FilesystemInterface;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\GitLFS\StreamFilter\StreamFilter;
use Tuleap\GitLFS\Transfer\BytesAmountHandledLFSObjectInstrumentationFilter;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class FileDownloaderController implements DispatchableWithRequest
{
    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;

    /**
     * @var LFSObjectRetriever
     */
    private $lfs_object_retriever;

    /**
     * @var LFSObjectPathAllocator
     */
    private $path_allocator;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;
    /**
     * @var Prometheus
     */
    private $prometheus;

    public function __construct(
        GitRepositoryFactory $git_repository_factory,
        LFSObjectRetriever $lfs_object_retriever,
        LFSObjectPathAllocator $path_allocator,
        FilesystemInterface $filesystem,
        Prometheus $prometheus
    ) {
        $this->git_repository_factory = $git_repository_factory;
        $this->lfs_object_retriever   = $lfs_object_retriever;
        $this->path_allocator         = $path_allocator;
        $this->filesystem             = $filesystem;
        $this->prometheus             = $prometheus;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');
        $repository = $this->git_repository_factory->getRepositoryById($variables['repo_id']);

        if ($repository === null || ! $repository->userCanRead($request->getCurrentUser())) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        session_write_close();

        $lfs_object = $this->lfs_object_retriever->getLFSObjectForRepository(
            $repository,
            $variables['oid']
        );

        if ($lfs_object === null) {
            throw new NotFoundException(dgettext('tuleap-gitlfs', 'The provided LFS object does not exist.'));
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment');
        header('Content-Length: ' . $lfs_object->getSize());
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; form-action 'none';");
        header('X-DNS-Prefetch-Control: off');

        ob_end_flush();

        $object_resource = $this->filesystem->readStream($this->path_allocator->getPathForAvailableObject($lfs_object));

        $transmitted_bytes_instrumentation_filter = BytesAmountHandledLFSObjectInstrumentationFilter::buildTransmittedBytesFilter(
            $this->prometheus,
            'webui'
        );
        $output_resource                          = fopen('php://output', 'ab');
        $transmitted_bytes_filter_handle          = StreamFilter::prependFilter($object_resource, $transmitted_bytes_instrumentation_filter);

        stream_copy_to_stream($object_resource, $output_resource);

        StreamFilter::removeFilter($transmitted_bytes_filter_handle);
    }
}
