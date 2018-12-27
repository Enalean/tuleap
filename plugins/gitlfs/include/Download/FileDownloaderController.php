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

namespace Tuleap\GitLFS\Download;

use Feedback;
use GitRepositoryFactory;
use HTTPRequest;
use League\Flysystem\FilesystemInterface;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
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

    public function __construct(
        GitRepositoryFactory $git_repository_factory,
        LFSObjectRetriever $lfs_object_retriever,
        LFSObjectPathAllocator $path_allocator,
        FilesystemInterface $filesystem
    ) {
        $this->git_repository_factory = $git_repository_factory;
        $this->lfs_object_retriever   = $lfs_object_retriever;
        $this->path_allocator         = $path_allocator;
        $this->filesystem             = $filesystem;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout $layout
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');
        $repository = $this->git_repository_factory->getRepositoryById($variables['repo_id']);

        if (! $repository->userCanRead($request->getCurrentUser())) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-gitlfs', 'You are not allowed to access this Git repository.')
            );
            $layout->redirect('/');
            return;
        }

        session_write_close();

        $lfs_object = $this->lfs_object_retriever->getLFSObjectForRepository(
            $repository,
            $variables['oid']
        );

        if ($lfs_object === null) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-gitlfs', 'The provided LFS object does not exist.')
            );
            $layout->redirect('/');
            return;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment');
        header('Content-Length: ' . $lfs_object->getSize());
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; form-action 'none';");
        header('X-DNS-Prefetch-Control: off');

        ob_end_flush();

        fpassthru($this->filesystem->readStream($this->path_allocator->getPathForAvailableObject($lfs_object)));

        return;
    }
}
