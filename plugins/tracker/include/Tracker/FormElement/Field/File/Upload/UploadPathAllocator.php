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

use Tracker_FormElement_Field_File;
use Tracker_FormElementFactory;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Upload\PathAllocator;
use Tuleap\Upload\UploadPathAllocator as DelegatedUploadPathAllocator;

final class UploadPathAllocator implements PathAllocator
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

    public function getPathForItemBeingUploaded(TusFileInformation $file_information): string
    {
        return $this->getDelegatedUploadPathAllocator($file_information)
            ->getPathForItemBeingUploaded($file_information);
    }

    private function getDelegatedUploadPathAllocator(TusFileInformation $file_information): DelegatedUploadPathAllocator
    {
        $field = $this->getFieldFromFileInformation($file_information);

        return $this->getDelegatedUploadPathAllocatorForField($field);
    }

    private function getDelegatedUploadPathAllocatorForField(
        Tracker_FormElement_Field_File $field,
    ): DelegatedUploadPathAllocator {
        return new DelegatedUploadPathAllocator($field->getRootPath());
    }

    private function getFieldFromFileInformation(TusFileInformation $file_information): Tracker_FormElement_Field_File
    {
        $row = $this->dao->searchFileOngoingUploadById($file_information->getID());
        if (! $row) {
            throw new \RuntimeException('Cannot retrieve field from file information');
        }

        return $this->getFileField((int) ($row['field_id']));
    }

    private function getFileField(int $field_id): Tracker_FormElement_Field_File
    {
        $field = $this->form_element_factory->getFieldById($field_id);
        \assert($field instanceof Tracker_FormElement_Field_File);

        if (! $field) {
            throw new \RuntimeException('Unable to find field for the file.');
        }

        if (! $this->form_element_factory->isFieldAFileField($field)) {
            throw new \RuntimeException('Field should be of type File.');
        }

        return $field;
    }
}
