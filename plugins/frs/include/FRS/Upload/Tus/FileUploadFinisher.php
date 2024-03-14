<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\FRS\Upload\Tus;

use FRSFile;
use FRSFileDao;
use FRSLogDao;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\FRS\Upload\FileOngoingUploadDao;
use Tuleap\FRS\Upload\UploadPathAllocator;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusFinisherDataStore;

class FileUploadFinisher implements TusFinisherDataStore
{
    /**
     * @var UploadPathAllocator
     */
    private $path_allocator;
    /**
     * @var \FRSFileFactory
     */
    private $file_factory;
    /**
     * @var FileOngoingUploadDao
     */
    private $dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var FRSLogDao
     */
    private $log_dao;
    /**
     * @var FRSFileDao
     */
    private $file_dao;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \FRSReleaseFactory
     */
    private $release_factory;
    /**
     * @var ToBeCreatedFRSFileBuilder
     */
    private $frs_file_builder;

    public function __construct(
        LoggerInterface $logger,
        UploadPathAllocator $path_allocator,
        \FRSFileFactory $file_factory,
        \FRSReleaseFactory $release_factory,
        FileOngoingUploadDao $dao,
        DBTransactionExecutor $transaction_executor,
        FRSFileDao $file_dao,
        FRSLogDao $log_dao,
        ToBeCreatedFRSFileBuilder $frs_file_builder,
    ) {
        $this->logger               = $logger;
        $this->path_allocator       = $path_allocator;
        $this->file_factory         = $file_factory;
        $this->release_factory      = $release_factory;
        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
        $this->log_dao              = $log_dao;
        $this->file_dao             = $file_dao;
        $this->frs_file_builder     = $frs_file_builder;
    }

    public function finishUpload(ServerRequestInterface $request, TusFileInformation $file_information): void
    {
        $this->finishUploadFile($file_information);
    }

    public function finishUploadFile(TusFileInformation $file_information): void
    {
        $id       = $file_information->getID();
        $filepath = $this->path_allocator->getPathForItemBeingUploaded($file_information);

        $current_value_user_abort = (bool) ignore_user_abort(true);
        try {
            $this->createFile($filepath, $id);
        } finally {
            ignore_user_abort($current_value_user_abort);
        }
        if (\is_file($filepath)) {
            \unlink($filepath);
        }
        $dir = dirname($filepath);
        if (\is_dir($dir)) {
            \rmdir($dir);
        }
        $this->dao->deleteByItemID($id);
    }

    private function createFile(string $filepath, int $id): void
    {
        $this->transaction_executor->execute(
            function () use ($filepath, $id) {
                $row = $this->dao->searchFileOngoingUploadById($id);
                if (empty($row)) {
                    $this->logger->info("Uploading file #$id could not found in the DB to be marked as uploaded");

                    return;
                }

                $release = $this->release_factory->getFRSReleaseFromDb($row['release_id']);
                if ($release === null) {
                    $this->logger->error("Release #{$row['release_id']} to upload file cannot be found");

                    return;
                }

                $project  = $release->getProject();
                $new_file = $this->frs_file_builder->buildFRSFile(
                    $release,
                    $row['name'],
                    (int) $row['file_size'],
                    (int) $row['user_id']
                );

                if (! $this->file_factory->moveFileForgeFromSrcDir($project, $release, $new_file, dirname($filepath))) {
                    throw new \RuntimeException("Not able to move file #$id");
                }

                $file_id = $this->file_dao->createFromArray($new_file->toArray());
                if ($file_id === false) {
                    throw new \RuntimeException("Not able to create file #$id in DB");
                }

                $this->log_dao->addLog($row['user_id'], $project->getID(), $file_id, FRSFile::EVT_CREATE);
            }
        );
    }
}
