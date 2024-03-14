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

namespace Tuleap\Tracker\FormElement\Field\File\Upload\Tus;

use Psr\Http\Message\ServerRequestInterface;
use Tracker_FileInfo;
use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusFinisherDataStore;

class FileUploadFinisher implements TusFinisherDataStore
{
    /**
     * @var FileOngoingUploadDao
     */
    private $dao;
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(FileOngoingUploadDao $dao, Tracker_FormElementFactory $form_element_factory)
    {
        $this->dao                  = $dao;
        $this->form_element_factory = $form_element_factory;
    }

    public function finishUpload(ServerRequestInterface $request, TusFileInformation $file_information): void
    {
        $row = $this->dao->searchFileOngoingUploadById($file_information->getID());
        if (! $row) {
            throw new \RuntimeException('Cannot retrieve field from file information');
        }
        $field = $this->form_element_factory->getFieldById($row['field_id']);
        if (! $field) {
            throw new \RuntimeException('Cannot retrieve field from file information');
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

        $file_info->postUploadActions();
    }
}
