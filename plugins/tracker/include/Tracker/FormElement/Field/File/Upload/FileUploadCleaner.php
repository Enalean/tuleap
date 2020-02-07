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

namespace Tuleap\Tracker\FormElement\Field\File\Upload;

use Tracker_FileInfo;
use Tracker_FormElementFactory;
use Tuleap\DB\DBTransactionExecutor;

final class FileUploadCleaner
{
    /**
     * @var FileOngoingUploadDao
     */
    private $dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        FileOngoingUploadDao $dao,
        Tracker_FormElementFactory $form_element_factory,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->logger               = $logger;
        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
        $this->form_element_factory = $form_element_factory;
    }

    public function deleteDanglingFilesToUpload(\DateTimeImmutable $current_time)
    {
        $this->logger->info('Deleting dangling files to upload.');
        $this->transaction_executor->execute(
            function () use ($current_time): void {
                $current_timestamp = $current_time->getTimestamp();
                $rows              = $this->dao->searchUnusableFiles($current_timestamp);
                $this->logger->info('Found ' . count($rows) . ' dangling files.');

                foreach ($rows as $row) {
                    $this->deleteUploadedFile($row);
                }
                $this->dao->deleteUnusableFiles($current_timestamp);
            }
        );
    }

    private function deleteUploadedFile(array $row): void
    {
        $field = $this->form_element_factory->getFieldById($row['field_id']);
        if (! $field) {
            $this->logger->error('Unable to find field from uploaded file with id ' . $row['id']);

            return;
        }

        $file_info = new Tracker_FileInfo(
            $row['id'],
            $field,
            $row['submitted_by'],
            $row['description'],
            $row['filename'],
            $row['filesize'],
            $row['filetype']
        );

        $file_info->deleteFiles();
    }
}
